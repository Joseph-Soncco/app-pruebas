# Railway Environment Configuration
# Copy these variables to Railway service settings

# Database Configuration (if using Railway MySQL)
DB_HOST=${{MYSQL_HOST}}
DB_USER=${{MYSQL_USER}}
DB_PASSWORD=${{MYSQL_PASSWORD}}
DB_NAME=${{MYSQL_DATABASE}}
DB_PORT=3306

# Application Configuration
CI_ENVIRONMENT=production

# Security
JWT_SECRET=railway_jwt_secret_key_change_this_in_production

# Railway automatically sets:
# PORT (automatically assigned)
# RAILWAY_PUBLIC_DOMAIN (automatically assigned)
# RAILWAY_HEALTHCHECK_TIMEOUT_SEC (optional, default 300)
