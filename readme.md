
[![Build Status](http://drone.uppdragshuset.se/api/badges/uppdragshuset/automatch-api/status.svg)](http://drone.uppdragshuset.se/uppdragshuset/automatch-api)



## Automatch-api
Either Download zip or clone project using 
```
https://github.com/uppdragshuset/automatch-api.git
```

Go to automatch-api folder in terminal
```
$ cd automatch-api
```
Copy ```.env.example``` to ```.env``` 
```
$ cp .env.example .env
```

composer install and key generate
```
$ composer install
$ php artisan key:generate
```

open .env using any editor/vim/nano and change following code to 
```
CACHE_DRIVER=file
QUEUE_DRIVER=sync
```
to 

```
CACHE_DRIVER=redis
QUEUE_DRIVER=beanstalkd
```

