<?php

namespace App\Jobs;

use App\Http\Controllers\AutomatchController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class RequestMaker implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $query;
    protected $rows;
    protected $key;
    protected $view;
    public function __construct($query, $rows, $key, $view)
    {
        $this->query = $query;
        $this->rows = $rows;
        $this->key = $key;
        $this->view = $view;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(AutomatchController $obj)
    {
        $searchdata = $obj->makeSearchRequest($this->query, $this->rows);
        Cache::put($this->key,$searchdata,15);
    }
}
