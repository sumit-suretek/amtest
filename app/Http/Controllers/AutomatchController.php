<?php

namespace App\Http\Controllers;

use App\Exceptions\WrongRequestParameterException;
use App\Jobs\RequestMaker;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AutomatchController extends Controller
{	
	/**
     * @var solrSearchUrl
    */
    protected $solrSearchUrl;

    /**
     * @var docdbUrl
    */
    protected $docdbUrl;

    /**
     * @var imageUrl
    */
    protected $imageUrl;

    public function __construct()
    {
        $this->solrSearchUrl = config('constants.SOLR_API').'/v1/';        
        $this->docdbUrl      = config('constants.DOCDB_API').'/v1/';        
        $this->imageUrl      = config('constants.IMAGE_API').'/v1/';
        $this->client        = new Client();
    }

    /**
     * Request search query 
     *
     * @var int $rows
     * @throws WrongRequestParameterException
     * @return object
     */
    public function search()
    {
             
    	$rows = (request()->input('requested_hits', config('solr_parameters.parameters.requested_hits')));
    	if (!ctype_digit(strval($rows)) || $rows <= 0) {
            throw new WrongRequestParameterException("The requested hits must be an integer. The requested hits must be at least 1.");
        } elseif (!ctype_digit(strval($rows)) || $rows > 100) {
        	throw new WrongRequestParameterException("The requested hits may not be greater than 100.");
        } 

        if (empty(request()->input('query'))) {
            throw new WrongRequestParameterException("Query parameter should not be blank.");
        }

        if (empty(request()->input('reference_number'))) {
            throw new WrongRequestParameterException("Reference Number should not be blank.");
        }

        if (strlen(trim(request()->input('reference_number'))) > 100) {
            throw new WrongRequestParameterException("Reference Number size should not be greater than 100.");
        }

        // checking for view parameters  

        if (!empty(request()->input('view'))) {
            $viewParameters = explode(',', request()->input('view'));
            $this->viewParamValidation($viewParameters);    
        }
        
        $key = request()->input('query').'-'.$rows;

        if (Cache::has($key)) {
            $keyval = Cache::get($key);
            if ($keyval <> '0') {
                if (!isset($viewParameters)) {
                    return response($keyval,200)->header('X-Reference-Number',request()->input('reference_number'));
                } else {
                    $viewAllParameter = array('bibliographic', 'claim', 'description', 'image');
                    $removeArr = array_diff($viewAllParameter, $viewParameters);
                    
                    foreach ($keyval->getData()->data->automatch_results as $key => $value) {
                        foreach ($removeArr as $key2 => $value2) {
                            unset($value->$value2);
                        }
                        $data['automatch_results'][] = $value;
                    }
                    return response()->resource_fetched($data)->header('X-Reference-Number',request()->input('reference_number'));
                    
                }
            }
        } else {
            Cache::put($key,'0',1);
            dispatch(new RequestMaker(request()->input('query'), $rows, $key, request()->input('view')));
        }
       
        return response()->json(["status" => "success","message" => "Processing",
            "data" => null],202);
    }

    private function viewParamValidation($viewParam)
    {
        // Checking for invalid view paramter

        try {
            array_map(function ($item){
                if($item == 'bibliographic' || $item == 'claim' || $item == 'description' || $item == 'image' || $item == 'pdf' ) {
                } else {
                    throw new WrongRequestParameterException("The view accepted values are: bibliographic, claim, description, image, pdf.");
                }
            }, $viewParam);    
        } catch (\Exception $e) {
            throw new WrongRequestParameterException($e->getMessage());
        } 
    }

    /**
     * Method for Solr Search Request
     *
     * @param $query
     * @param $rows
     * @throws WrongRequestParameterException
     * @return object
     */
    public function makeSearchRequest($query="", $rows="")
    {
        if($query == "" || $rows == "")
        {
            $query = request()->input('query');
            $rows = request()->input('rows');
        }
    	if (empty($query) || !isset($query)) {
    		throw new WrongRequestParameterException("Query parameter may not be blank");
    	}

    	$url = $this->solrSearchUrl .'search/';

        $response = $this->client->request(
            "POST", $url, [
            	'headers'   	=> ['API-TOKEN' => env('API_TOKEN'), ],
            	'form_params'	=> ['query' => $query, 'rows'	=> $rows]
            ]
        );

        $solrData = json_decode($response->getBody());
        return response()->resource_fetched($this->transformSolrSearchData($solrData));
    }


    /**
     * Method for Docdb Search Request
     *
     * @param $patentId
     * @throws WrongRequestParameterException
     * @return object
     */
    private function docdbSearchRequest($patentId)
    {   
        // Fetching Bibliographic Data
        $bibliographic = $this->bibliographicSearchRequest($patentId);

        // Fetching Claims and Description Data
        $claimDescription = $this->claimAndDescriptionSearchRequest($patentId);

        // Fetching Image Data
        $image = $this->imageResponseRequest($patentId);
        
        $result['bibliographic']    = $bibliographic;
        $result['claim']            = $claimDescription->claim;
        $result['description']      = $claimDescription->description;
        $result['image']            = $image;
            
        return $result;
    }


    /**
     * Method for Docdb Search Request
     *
     * @param $patentId
     * @throws WrongRequestParameterException
     * @return object
     */
    private function bibliographicSearchRequest($patentId)
    {
        try {
            $url = $this->docdbUrl .'getBibliographic/'.$patentId;
            $bibliographResponse = $this->client->request(
                "GET", $url, [
                    'headers'       => ['API-TOKEN' => env('API_TOKEN'), ]
                ]
            );
            $bibliographicResult = $this->transformDocdbData(json_decode($bibliographResponse->getBody()));
            return $bibliographicResult;
        } catch (\Exception $e) {
            throw new WrongRequestParameterException("Patent does not exist",404);
        }
    }


    /**
     * Method for Claim and Description Search Request
     *
     * @param $patentId
     * @throws WrongRequestParameterException
     * @return object
     */
    private function claimAndDescriptionSearchRequest($patentId)
    {
        try {
            $url = $this->docdbUrl .'getClaimAndDescription/'.$patentId;

            $claimAndDescription = $this->client->request(
                "GET", $url, [
                    'headers'       => ['API-TOKEN' => env('API_TOKEN'), ]
                ]
            );

            $claimDescriptionData = json_decode($claimAndDescription->getBody());

            unset($claimDescriptionData->status);
            unset($claimDescriptionData->message);
            unset($claimDescriptionData->data->claim->source);
            unset($claimDescriptionData->data->description->source);

            return $claimDescriptionData->data;   
        } catch (\Exception $e) {
            throw new WrongRequestParameterException("Patent does not exist",404);
        }
    }


    /**
     * Method for Image Search Request
     *
     * @param $patentId
     * @return object
     */
    private function imageResponseRequest($patentId)
    {
        try {
            $url = $this->imageUrl .'getImage/'.$patentId;
            $imageResponse = $this->client->request(
                "GET", $url, [
                    'headers'       => ['API-TOKEN' => env('API_TOKEN'), ]
                ]
            );
            $data = json_decode($imageResponse->getBody());
            unset($data->status);
            unset($data->message);
        }
        catch (\Exception $e) {
            $data['data'] = null;
        }
        return $data;
    }


    /**
     * Method for transform Solr search response
     *
     * @param $response
     * @return object
     */
    private function transformSolrSearchData($response)
    {
        unset($response->status);
        unset($response->message);


        // return $response;
        $response->automatch_results = array_map(function($item){
            $patentId = explode('-', $item->number);
            $bibliographicResponse = $this->docdbSearchRequest($item->number);

            unset($item->number);
            unset($item->document_type);

            $item->country = $patentId[0];
            $item->number = $patentId[1];
            $item->kind_code = $patentId[2];
            $item->family_number = $item->familynumber;

            unset($item->familynumber);
            
            $itemData['relevance'] = $item;

            if (!empty($bibliographicResponse['bibliographic'])) {
                $itemData['bibliographic'] = $bibliographicResponse['bibliographic'];    
            }
            if (!empty($bibliographicResponse['claim'])) {
                $itemData['claim'] = $bibliographicResponse['claim'];    
            }
            if (!empty($bibliographicResponse['description'])) {
                $itemData['description'] = $bibliographicResponse['description'];                
            }
            if (!empty($bibliographicResponse['image'])) {
                $itemData['image'] = $bibliographicResponse['image'];
            }
            return $itemData;
        },$response->data->docs);
        unset($response->data);
        return $response;
    }


    /**
     * Method for transform docdb response
     *
     * @param $bibliographRes
     * @var $docdbarr
     * @throws WrongRequestParameterException
     * @return object
     */
    private function transformDocdbData($bibliographRes)
    {
        unset($bibliographRes->status);
        unset($bibliographRes->message);
        
        $docdbarr = [];

        foreach ($bibliographRes->data->address as $key => $item) {
            if ($item->type == 'applicant') {
                unset($item->type);
                unset($item->format);
                $docdbarr['applicant'][] = $item;
            } elseif ($item->type == 'inventor') {
                unset($item->type);
                unset($item->format);
                $docdbarr['inventor'][] = $item;
            }
            
        }

        foreach ($bibliographRes->data->class as $key => $value) {
            unset($value->original);
            $docdbarr['class'][] = $value;
        }
        unset($bibliographRes->data->title[0]->format);
        unset($bibliographRes->data->abstract[0]->source);
        unset($bibliographRes->data->abstract[0]->format);
        $docdbarr['title'] = $bibliographRes->data->title;
        $docdbarr['abstract'] = $bibliographRes->data->abstract;
        
        return $docdbarr;        
    }
}
