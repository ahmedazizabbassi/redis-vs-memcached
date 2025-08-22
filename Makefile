# Cache Benchmark Tool - Makefile
# Simplifies common Docker operations

.PHONY: help build up down test clean logs shell benchmark dev web

# Default target
help:
	@echo "Cache Benchmark Tool - Available Commands:"
	@echo ""
	@echo "  build      - Build Docker images"
	@echo "  up         - Start all services (production)"
	@echo "  down       - Stop all services"
	@echo "  test       - Run Docker setup tests"
	@echo "  clean      - Clean up Docker resources"
	@echo "  logs       - View service logs"
	@echo "  shell      - Access application shell"
	@echo "  benchmark  - Run full benchmark suite"
	@echo "  dev        - Start development environment"
	@echo "  web        - Start with web interface"
	@echo "  quick      - Run quick benchmark test"
	@echo ""

# Build Docker images
build:
	@echo "🔨 Building Docker images..."
	docker-compose build

# Start production environment
up:
	@echo "🚀 Starting production environment..."
	docker-compose --profile benchmark up --build

# Start in detached mode
up-d:
	@echo "🚀 Starting production environment (detached)..."
	docker-compose --profile benchmark up --build -d

# Stop all services
down:
	@echo "🛑 Stopping all services..."
	docker-compose down

# Clean up Docker resources
clean:
	@echo "🧹 Cleaning up Docker resources..."
	docker-compose down -v --remove-orphans
	docker system prune -f

# View logs
logs:
	@echo "📋 Viewing service logs..."
	docker-compose logs -f

# View specific service logs
logs-app:
	@echo "📋 Viewing application logs..."
	docker-compose logs -f app

logs-redis:
	@echo "📋 Viewing Redis logs..."
	docker-compose logs -f redis

logs-memcached:
	@echo "📋 Viewing Memcached logs..."
	docker-compose logs -f memcached

# Access application shell
shell:
	@echo "🐚 Accessing application shell..."
	docker-compose exec app bash

# Access development shell
shell-dev:
	@echo "🐚 Accessing development shell..."
	docker-compose exec app-dev bash

# Run full benchmark suite
benchmark:
	@echo "🏃 Running full benchmark suite..."
	docker-compose --profile benchmark up --build --exit-code-from app

# Start development environment
dev:
	@echo "🔧 Starting development environment..."
	docker-compose --profile dev up --build

# Start with web interface
web:
	@echo "🌐 Starting with web interface..."
	docker-compose --profile benchmark --profile web up --build

# Run quick benchmark test
quick:
	@echo "⚡ Running quick benchmark test..."
	docker-compose --profile dev up -d
	@sleep 10
	docker-compose exec app-dev php bin/benchmark.php --iterations=50 --concurrent=5
	docker-compose down

# Run Docker setup tests
test:
	@echo "🧪 Running Docker setup tests..."
	./test-docker.sh

# Check service status
status:
	@echo "📊 Service status:"
	docker-compose ps

# Check service health
health:
	@echo "🏥 Checking service health..."
	@echo "Redis:"
	docker-compose exec redis redis-cli ping || echo "Redis not running"
	@echo "Memcached:"
	docker-compose exec memcached sh -c "echo 'version' | nc localhost 11211" || echo "Memcached not running"
	@echo "Application:"
	docker-compose exec app php -r "echo 'PHP: ' . phpversion() . PHP_EOL;" || echo "Application not running"

# Install dependencies
install:
	@echo "📦 Installing dependencies..."
	docker-compose exec app composer install

# Run tests
test-php:
	@echo "🧪 Running PHP tests..."
	docker-compose exec app composer test

# Generate documentation
docs:
	@echo "📚 Generating documentation..."
	@echo "Documentation files:"
	@ls -la *.md | grep -E "(README|QUICKSTART|DOCKER|DOCUMENTATION|TECHNICAL|MAINTENANCE)"

# Show configuration
config:
	@echo "⚙️  Docker Compose configuration:"
	docker-compose config

# Show environment
env:
	@echo "🌍 Environment variables:"
	@if [ -f .env ]; then cat .env; else echo "No .env file found"; fi

# Create environment file
env-create:
	@echo "📝 Creating environment file..."
	@if [ ! -f .env ]; then cp .env.example .env && echo "Created .env from .env.example"; else echo ".env already exists"; fi

# Backup results
backup:
	@echo "💾 Backing up results..."
	@if [ -d results ]; then tar -czf "results-backup-$(date +%Y%m%d-%H%M%S).tar.gz" results/ && echo "Backup created"; else echo "No results directory found"; fi

# Restore results
restore:
	@echo "📥 Restoring results..."
	@if [ -f results-backup-*.tar.gz ]; then tar -xzf results-backup-*.tar.gz && echo "Results restored"; else echo "No backup file found"; fi

# Monitor resources
monitor:
	@echo "📊 Monitoring Docker resources..."
	docker stats

# Show disk usage
disk-usage:
	@echo "💾 Docker disk usage:"
	docker system df

# Show Docker info
info:
	@echo "ℹ️  Docker information:"
	docker version
	docker-compose --version
	docker system info

# Restart services
restart:
	@echo "🔄 Restarting services..."
	docker-compose restart

# Scale services (example)
scale:
	@echo "📈 Scaling services..."
	@echo "Usage: make scale-redis N=3"
	@echo "Usage: make scale-memcached N=2"

scale-redis:
	@echo "📈 Scaling Redis to $(N) instances..."
	docker-compose up -d --scale redis=$(N)

scale-memcached:
	@echo "📈 Scaling Memcached to $(N) instances..."
	docker-compose up -d --scale memcached=$(N)
