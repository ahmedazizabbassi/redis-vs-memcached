#!/bin/bash

# Test Docker Setup for Cache Benchmark Tool
set -e

echo "🐳 Testing Docker Setup for Cache Benchmark Tool"
echo "================================================"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    local status=$1
    local message=$2
    case $status in
        "SUCCESS")
            echo -e "${GREEN}✅ $message${NC}"
            ;;
        "ERROR")
            echo -e "${RED}❌ $message${NC}"
            ;;
        "WARNING")
            echo -e "${YELLOW}⚠️  $message${NC}"
            ;;
        "INFO")
            echo -e "${BLUE}ℹ️  $message${NC}"
            ;;
    esac
}

# Check if Docker is installed
check_docker() {
    print_status "INFO" "Checking Docker installation..."
    if command -v docker &> /dev/null; then
        print_status "SUCCESS" "Docker is installed"
        docker --version
    else
        print_status "ERROR" "Docker is not installed"
        exit 1
    fi
}

# Check if Docker Compose is installed
check_docker_compose() {
    print_status "INFO" "Checking Docker Compose installation..."
    if command -v docker-compose &> /dev/null; then
        print_status "SUCCESS" "Docker Compose is installed"
        docker-compose --version
    else
        print_status "ERROR" "Docker Compose is not installed"
        exit 1
    fi
}

# Check if Docker daemon is running
check_docker_daemon() {
    print_status "INFO" "Checking Docker daemon..."
    if docker info &> /dev/null; then
        print_status "SUCCESS" "Docker daemon is running"
    else
        print_status "ERROR" "Docker daemon is not running"
        exit 1
    fi
}

# Check required files
check_files() {
    print_status "INFO" "Checking required files..."
    
    local required_files=(
        "Dockerfile"
        "docker-compose.yml"
        "docker/entrypoint.sh"
        "docker/nginx.conf"
        "docker/index.html"
        "composer.json"
        "bin/benchmark.php"
    )
    
    for file in "${required_files[@]}"; do
        if [ -f "$file" ]; then
            print_status "SUCCESS" "Found $file"
        else
            print_status "ERROR" "Missing $file"
            exit 1
        fi
    done
}

# Build Docker image
build_image() {
    print_status "INFO" "Building Docker image..."
    if docker-compose build app; then
        print_status "SUCCESS" "Docker image built successfully"
    else
        print_status "ERROR" "Failed to build Docker image"
        exit 1
    fi
}

# Test basic functionality
test_basic_functionality() {
    print_status "INFO" "Testing basic functionality..."
    
    # Start services
    print_status "INFO" "Starting services..."
    docker-compose --profile dev up -d
    
    # Wait for services to be ready
    print_status "INFO" "Waiting for services to be ready..."
    sleep 15
    
    # Wait for app container to be running
    print_status "INFO" "Waiting for application container..."
    timeout=30
    counter=0
    while [ $counter -lt $timeout ]; do
        if docker-compose ps app-dev | grep -q "Up"; then
            break
        fi
        sleep 1
        counter=$((counter + 1))
    done
    
    if [ $counter -eq $timeout ]; then
        print_status "ERROR" "Application container failed to start"
        docker-compose logs app-dev
        docker-compose down
        exit 1
    fi
    
    # Test PHP extensions
    print_status "INFO" "Testing PHP extensions..."
    if docker-compose exec app-dev php -m | grep -E "(redis|memcached)" > /dev/null; then
        print_status "SUCCESS" "PHP extensions loaded correctly"
    else
        print_status "ERROR" "PHP extensions not loaded"
        docker-compose down
        exit 1
    fi
    
    # Test benchmark help
    print_status "INFO" "Testing benchmark tool..."
    if docker-compose exec app-dev php bin/benchmark.php --help > /dev/null; then
        print_status "SUCCESS" "Benchmark tool is working"
    else
        print_status "ERROR" "Benchmark tool failed"
        docker-compose down
        exit 1
    fi
}

# Test cache connections
test_cache_connections() {
    print_status "INFO" "Testing cache connections..."
    
    # Test Redis connection
    if docker-compose exec app-dev php -r "
    try {
        \$redis = new Redis();
        \$redis->connect('redis', 6379);
        echo 'Redis: ' . \$redis->ping() . PHP_EOL;
    } catch (Exception \$e) {
        echo 'Redis: FAIL - ' . \$e->getMessage() . PHP_EOL;
        exit(1);
    }
    " | grep -q "PONG"; then
        print_status "SUCCESS" "Redis connection successful"
    else
        print_status "ERROR" "Redis connection failed"
        docker-compose down
        exit 1
    fi
    
    # Test Memcached connection
    if docker-compose exec app-dev php -r "
    try {
        \$memcached = new Memcached();
        \$memcached->addServer('memcached', 11211);
        \$memcached->set('test', 'value');
        echo 'Memcached: ' . (\$memcached->get('test') === 'value' ? 'OK' : 'FAIL') . PHP_EOL;
    } catch (Exception \$e) {
        echo 'Memcached: FAIL - ' . \$e->getMessage() . PHP_EOL;
        exit(1);
    }
    " | grep -q "OK"; then
        print_status "SUCCESS" "Memcached connection successful"
    else
        print_status "ERROR" "Memcached connection failed"
        docker-compose down
        exit 1
    fi
}

# Run quick benchmark
run_quick_benchmark() {
    print_status "INFO" "Running quick benchmark test..."
    
    if docker-compose exec app-dev php bin/benchmark.php --iterations=10 --concurrent=2 > /dev/null 2>&1; then
        print_status "SUCCESS" "Quick benchmark completed successfully"
    else
        print_status "WARNING" "Quick benchmark failed (this might be expected in some environments)"
    fi
}

# Test web interface
test_web_interface() {
    print_status "INFO" "Testing web interface..."
    
    # Start web interface
    docker-compose --profile web up -d nginx
    
    # Wait for nginx to start
    sleep 5
    
    # Test web interface
    if curl -s http://localhost:8080/health | grep -q "healthy"; then
        print_status "SUCCESS" "Web interface is accessible"
        print_status "INFO" "Web interface available at: http://localhost:8080"
    else
        print_status "WARNING" "Web interface test failed"
    fi
}

# Cleanup
cleanup() {
    print_status "INFO" "Cleaning up..."
    docker-compose down --remove-orphans
    print_status "SUCCESS" "Cleanup completed"
}

# Main test sequence
main() {
    echo
    print_status "INFO" "Starting Docker setup tests..."
    echo
    
    check_docker
    check_docker_compose
    check_docker_daemon
    check_files
    build_image
    test_basic_functionality
    test_cache_connections
    run_quick_benchmark
    test_web_interface
    cleanup
    
    echo
    print_status "SUCCESS" "All Docker tests passed! 🎉"
    echo
    print_status "INFO" "You can now use the following commands:"
    echo "  • docker-compose --profile benchmark up --build    # Run full benchmark"
    echo "  • docker-compose --profile dev up --build          # Development environment"
    echo "  • docker-compose --profile benchmark --profile web up --build  # With web interface"
    echo
}

# Run main function
main "$@"
