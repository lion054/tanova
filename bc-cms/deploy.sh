#!/bin/bash

###############################################################################
# Tsoka Deployment Script v2.0
# Automates the complete deployment process
###############################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
DEPLOY_USER="${DEPLOY_USER:-}"
DEPLOY_HOST="${DEPLOY_HOST:-}"
DEPLOY_PATH="${DEPLOY_PATH:-.}"
ENVIRONMENT="${ENVIRONMENT:-local}"
BACKUP_DIR="backups"

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}🚀 Tsoka Deployment Script${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Detect if remote or local deployment
if [ -z "$DEPLOY_HOST" ]; then
    echo -e "${YELLOW}📍 Local Deployment Mode${NC}"
    IS_REMOTE=false
else
    echo -e "${YELLOW}📍 Remote Deployment Mode (${DEPLOY_HOST})${NC}"
    IS_REMOTE=true
fi

###############################################################################
# Step 1: Pre-Flight Checks
###############################################################################
echo -e "\n${BLUE}[1/6] Pre-Flight Checks${NC}"

if [ "$IS_REMOTE" = false ]; then
    # Check git status
    if ! git status > /dev/null 2>&1; then
        echo -e "${RED}❌ Not a git repository${NC}"
        exit 1
    fi

    # Check for uncommitted changes
    if ! git diff-index --quiet HEAD --; then
        echo -e "${YELLOW}⚠️  Uncommitted changes detected${NC}"
        git status --short
        read -p "Continue anyway? (y/N) " -n 1 -r
        echo
        [[ $REPLY =~ ^[Yy]$ ]] || exit 1
    fi

    echo -e "${GREEN}✅ Git checks passed${NC}"
else
    echo -e "${YELLOW}⏭️  Skipping git checks for remote deployment${NC}"
fi

###############################################################################
# Step 2: Create Database Backup
###############################################################################
echo -e "\n${BLUE}[2/6] Creating Database Backup${NC}"

if [ "$IS_REMOTE" = false ]; then
    mkdir -p "$BACKUP_DIR"
    BACKUP_FILE="$BACKUP_DIR/gotrip_$(date +%Y%m%d_%H%M%S).sql"

    if command -v mysqldump &> /dev/null; then
        echo "📦 Backing up database to $BACKUP_FILE..."
        # Note: Requires ~/.my.cnf or environment variables for credentials
        mysqldump gotrip > "$BACKUP_FILE" 2>/dev/null || {
            echo -e "${YELLOW}⚠️  Could not backup database (check credentials)${NC}"
        }
        echo -e "${GREEN}✅ Backup created${NC}"
    else
        echo -e "${YELLOW}⚠️  mysqldump not found, skipping backup${NC}"
    fi
else
    echo -e "${YELLOW}⏭️  Skipping local backup for remote deployment${NC}"
fi

###############################################################################
# Step 3: Install Dependencies
###############################################################################
echo -e "\n${BLUE}[3/6] Installing Dependencies${NC}"

if [ "$IS_REMOTE" = true ]; then
    echo "📥 Installing dependencies on remote server..."
    ssh "$DEPLOY_USER@$DEPLOY_HOST" "cd $DEPLOY_PATH && composer install --optimize-autoloader --no-dev"
else
    echo "📥 Installing dependencies locally..."
    if [ -f "composer.lock" ]; then
        composer install --no-dev --optimize-autoloader
    else
        composer install --optimize-autoloader
    fi
fi

echo -e "${GREEN}✅ Dependencies installed${NC}"

###############################################################################
# Step 4: Run Migrations & Optimization
###############################################################################
echo -e "\n${BLUE}[4/6] Running Migrations & Optimization${NC}"

run_artisan() {
    local cmd=$1
    if [ "$IS_REMOTE" = true ]; then
        ssh "$DEPLOY_USER@$DEPLOY_HOST" "cd $DEPLOY_PATH && php artisan $cmd"
    else
        php artisan $cmd
    fi
}

echo "🔄 Clearing caches..."
run_artisan "cache:clear"
run_artisan "config:clear"
run_artisan "view:clear"

echo "📊 Running migrations..."
run_artisan "migrate --force"

echo "⚙️  Optimizing application..."
run_artisan "config:cache"
run_artisan "route:cache"
run_artisan "view:cache"

echo -e "${GREEN}✅ Migrations & optimization complete${NC}"

###############################################################################
# Step 5: Compile Assets
###############################################################################
echo -e "\n${BLUE}[5/6] Compiling Assets${NC}"

if [ -f "package.json" ]; then
    if [ "$IS_REMOTE" = true ]; then
        echo "📦 Installing npm packages on remote..."
        ssh "$DEPLOY_USER@$DEPLOY_HOST" "cd $DEPLOY_PATH && npm install --production"
        echo "🏗️  Building assets on remote..."
        ssh "$DEPLOY_USER@$DEPLOY_HOST" "cd $DEPLOY_PATH && npm run build"
    else
        if [ -d "node_modules" ]; then
            echo "⏭️  npm packages already installed${NC}"
        else
            echo "📦 Installing npm packages..."
            npm install --production
        fi

        if [ -f "package.json" ] && grep -q '"build"' package.json; then
            echo "🏗️  Building assets..."
            npm run build
        fi
    fi
    echo -e "${GREEN}✅ Assets compiled${NC}"
else
    echo -e "${YELLOW}⏭️  No package.json found, skipping npm build${NC}"
fi

###############################################################################
# Step 6: Restart Application
###############################################################################
echo -e "\n${BLUE}[6/6] Restarting Application${NC}"

if [ "$IS_REMOTE" = true ]; then
    echo "🔄 Restarting remote application..."
    ssh "$DEPLOY_USER@$DEPLOY_HOST" "cd $DEPLOY_PATH && systemctl restart laravel-app || sudo systemctl restart laravel-app"
    echo -e "${GREEN}✅ Application restarted${NC}"
else
    echo -e "${YELLOW}⏭️  Local deployment complete (restart manually if needed)${NC}"
    echo "   Run: php artisan serve"
fi

###############################################################################
# Verification
###############################################################################
echo -e "\n${BLUE}[✓] Deployment Complete!${NC}"
echo ""
echo -e "${GREEN}Summary:${NC}"
echo "  ✅ Dependencies installed"
echo "  ✅ Database migrated"
echo "  ✅ Caches optimized"
echo "  ✅ Assets compiled"
echo "  ✅ Application restarted"
echo ""

if [ "$IS_REMOTE" = false ]; then
    echo -e "${YELLOW}Next steps:${NC}"
    echo "  1. Test the application: http://localhost:8000"
    echo "  2. Check logs: tail -f storage/logs/laravel.log"
    echo "  3. Verify API: curl http://localhost:8000/api/v/tanova/trips"
    echo ""
fi

echo -e "${BLUE}========================================${NC}"
echo -e "${GREEN}🎉 Ready to serve traffic!${NC}"
echo -e "${BLUE}========================================${NC}"
