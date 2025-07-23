#!/bin/bash

# SuiteCRM Optimized Development Server
echo "Starting optimized SuiteCRM development server..."

# Kill any existing PHP servers
pkill -f "php.*-S.*localhost"

# Start optimized PHP server
php -c php.ini.local \
    -d session.save_path=tmp/sessions \
    -d session.auto_start=1 \
    -d opcache.enable=1 \
    -d opcache.memory_consumption=256 \
    -d memory_limit=512M \
    -d max_execution_time=300 \
    -d error_reporting=E_ERROR \
    -d display_errors=0 \
    -d realpath_cache_size=4096K \
    -S localhost:3000 \
    -t . &

echo "Server started on http://localhost:3000"
echo "Server PID: $!"
echo "To stop: pkill -f 'php.*-S.*localhost'" 