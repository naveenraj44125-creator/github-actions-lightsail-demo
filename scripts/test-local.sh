#!/bin/bash

# Local testing script for the Node.js application
# This script helps you test the application locally before deployment

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}Local Application Testing${NC}"
echo "========================"

# Check if Node.js is installed
check_nodejs() {
    echo -e "${YELLOW}Checking Node.js installation...${NC}"
    
    if ! command -v node &> /dev/null; then
        echo -e "${RED}Error: Node.js is not installed${NC}"
        echo "Please install Node.js: https://nodejs.org/"
        exit 1
    fi
    
    if ! command -v npm &> /dev/null; then
        echo -e "${RED}Error: npm is not installed${NC}"
        echo "Please install npm (usually comes with Node.js)"
        exit 1
    fi
    
    NODE_VERSION=$(node --version)
    NPM_VERSION=$(npm --version)
    
    echo -e "${GREEN}✓ Node.js ${NODE_VERSION} installed${NC}"
    echo -e "${GREEN}✓ npm ${NPM_VERSION} installed${NC}"
}

# Install dependencies
install_dependencies() {
    echo -e "${YELLOW}Installing dependencies...${NC}"
    
    cd app
    
    if [ ! -f "package.json" ]; then
        echo -e "${RED}Error: package.json not found${NC}"
        exit 1
    fi
    
    npm install
    echo -e "${GREEN}✓ Dependencies installed${NC}"
}

# Run tests
run_tests() {
    echo -e "${YELLOW}Running tests...${NC}"
    
    if npm test; then
        echo -e "${GREEN}✓ All tests passed${NC}"
    else
        echo -e "${RED}✗ Tests failed${NC}"
        exit 1
    fi
}

# Start application in background
start_application() {
    echo -e "${YELLOW}Starting application...${NC}"
    
    # Kill any existing process on port 3000
    if lsof -ti:3000 >/dev/null 2>&1; then
        echo "Killing existing process on port 3000..."
        kill -9 $(lsof -ti:3000) 2>/dev/null || true
        sleep 2
    fi
    
    # Start the application in background
    npm start &
    APP_PID=$!
    
    echo "Application started with PID: $APP_PID"
    
    # Wait for application to start
    echo "Waiting for application to start..."
    sleep 5
    
    # Check if process is still running
    if ! kill -0 $APP_PID 2>/dev/null; then
        echo -e "${RED}✗ Application failed to start${NC}"
        exit 1
    fi
    
    echo -e "${GREEN}✓ Application started successfully${NC}"
}

# Test endpoints
test_endpoints() {
    echo -e "${YELLOW}Testing application endpoints...${NC}"
    
    BASE_URL="http://localhost:3000"
    
    # Test root endpoint
    echo "Testing GET /"
    if curl -s -f "$BASE_URL/" > /dev/null; then
        echo -e "${GREEN}✓ Root endpoint working${NC}"
    else
        echo -e "${RED}✗ Root endpoint failed${NC}"
        return 1
    fi
    
    # Test health endpoint
    echo "Testing GET /health"
    if curl -s -f "$BASE_URL/health" > /dev/null; then
        echo -e "${GREEN}✓ Health endpoint working${NC}"
    else
        echo -e "${RED}✗ Health endpoint failed${NC}"
        return 1
    fi
    
    # Test API info endpoint
    echo "Testing GET /api/info"
    if curl -s -f "$BASE_URL/api/info" > /dev/null; then
        echo -e "${GREEN}✓ API info endpoint working${NC}"
    else
        echo -e "${RED}✗ API info endpoint failed${NC}"
        return 1
    fi
    
    # Test deploy info endpoint
    echo "Testing GET /api/deploy-info"
    if curl -s -f "$BASE_URL/api/deploy-info" > /dev/null; then
        echo -e "${GREEN}✓ Deploy info endpoint working${NC}"
    else
        echo -e "${RED}✗ Deploy info endpoint failed${NC}"
        return 1
    fi
    
    # Test 404 handling
    echo "Testing 404 handling"
    if curl -s "$BASE_URL/nonexistent" | grep -q "Not Found"; then
        echo -e "${GREEN}✓ 404 handling working${NC}"
    else
        echo -e "${RED}✗ 404 handling failed${NC}"
        return 1
    fi
    
    echo -e "${GREEN}✓ All endpoints working correctly${NC}"
}

# Show application info
show_application_info() {
    echo -e "${YELLOW}Getting application information...${NC}"
    
    BASE_URL="http://localhost:3000"
    
    echo
    echo "=== Application Information ==="
    curl -s "$BASE_URL/" | jq '.' 2>/dev/null || curl -s "$BASE_URL/"
    
    echo
    echo "=== Health Status ==="
    curl -s "$BASE_URL/health" | jq '.' 2>/dev/null || curl -s "$BASE_URL/health"
    
    echo
    echo "=== API Information ==="
    curl -s "$BASE_URL/api/info" | jq '.' 2>/dev/null || curl -s "$BASE_URL/api/info"
}

# Cleanup function
cleanup() {
    echo -e "${YELLOW}Cleaning up...${NC}"
    
    if [ ! -z "$APP_PID" ] && kill -0 $APP_PID 2>/dev/null; then
        echo "Stopping application (PID: $APP_PID)..."
        kill $APP_PID 2>/dev/null || true
        sleep 2
        
        # Force kill if still running
        if kill -0 $APP_PID 2>/dev/null; then
            kill -9 $APP_PID 2>/dev/null || true
        fi
    fi
    
    # Kill any remaining processes on port 3000
    if lsof -ti:3000 >/dev/null 2>&1; then
        kill -9 $(lsof -ti:3000) 2>/dev/null || true
    fi
    
    echo -e "${GREEN}✓ Cleanup completed${NC}"
}

# Main execution
main() {
    echo "Starting local application test..."
    echo
    
    check_nodejs
    install_dependencies
    run_tests
    start_application
    
    if test_endpoints; then
        show_application_info
        echo
        echo -e "${GREEN}🎉 All tests passed! Application is working correctly.${NC}"
        echo
        echo "Application is running at: http://localhost:3000"
        echo "Health check: http://localhost:3000/health"
        echo
        echo "Press Ctrl+C to stop the application"
        
        # Keep application running until user stops it
        trap cleanup EXIT
        wait $APP_PID
    else
        echo -e "${RED}✗ Some tests failed${NC}"
        cleanup
        exit 1
    fi
}

# Handle script interruption
trap cleanup EXIT INT TERM

# Parse command line arguments
SKIP_TESTS=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --skip-tests)
            SKIP_TESTS=true
            shift
            ;;
        --help)
            echo "Usage: $0 [OPTIONS]"
            echo "Options:"
            echo "  --skip-tests    Skip running tests"
            echo "  --help          Show this help message"
            exit 0
            ;;
        *)
            echo "Unknown option: $1"
            echo "Use --help for usage information"
            exit 1
            ;;
    esac
done

# Run main function
if [ "$SKIP_TESTS" = true ]; then
    echo "Skipping tests as requested"
    check_nodejs
    install_dependencies
    start_application
    test_endpoints && show_application_info
    echo "Press Ctrl+C to stop the application"
    wait $APP_PID
else
    main
fi
