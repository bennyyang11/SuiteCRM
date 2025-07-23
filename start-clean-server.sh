#!/bin/bash
# Clean SuiteCRM Manufacturing Demo Startup Script

echo "🏭 Starting SuiteCRM Manufacturing Demo (Clean Mode)"

# Kill any existing PHP servers
pkill -f "php.*localhost:3000" 2>/dev/null

# Start with clean configuration
php -c php.ini.clean \
    -d session.save_path=tmp/sessions \
    -d session.auto_start=1 \
    -d memory_limit=1024M \
    -d max_execution_time=300 \
    -d error_reporting="E_ALL & ~E_DEPRECATED & ~E_STRICT" \
    -d display_errors=0 \
    -d log_errors=1 \
    -d error_log=suitecrm_clean.log \
    -S localhost:3000 -t . > server_clean.log 2>&1 &

SERVER_PID=$!
echo "🚀 Clean server started on localhost:3000 (PID: $SERVER_PID)"
echo "📊 Demo URL: http://localhost:3000/manufacturing_demo.php"
echo "🔧 API Test: http://localhost:3000/test_manufacturing_apis.php"
echo "📝 Server log: server_clean.log"
echo "❌ Error log: suitecrm_clean.log"

# Wait a moment and test
sleep 3
if curl -s -f "http://localhost:3000/manufacturing_demo.php" > /dev/null; then
    echo "✅ Server is responding correctly"
else
    echo "❌ Server is not responding"
fi
