#!/bin/bash

service supervisor start
supervisorctl reread
supervisorctl update
supervisorctl start worker:*

service php7.0-fpm start
service nginx start
