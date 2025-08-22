#!/bin/bash

# Install PHP memcached extension with correct paths for macOS
echo "Installing PHP memcached extension..."

# Set environment variables for the installation
export PKG_CONFIG_PATH="/opt/homebrew/lib/pkgconfig:/opt/homebrew/opt/zlib/lib/pkgconfig:/opt/homebrew/opt/libmemcached/lib/pkgconfig:$PKG_CONFIG_PATH"
export CPPFLAGS="-I/opt/homebrew/opt/zlib/include -I/opt/homebrew/opt/libmemcached/include $CPPFLAGS"
export LDFLAGS="-L/opt/homebrew/opt/zlib/lib -L/opt/homebrew/opt/libmemcached/lib $LDFLAGS"

# Install memcached extension
echo "Running pecl install with custom paths..."
echo "libmemcached directory: /opt/homebrew/opt/libmemcached"
echo "zlib directory: /opt/homebrew/opt/zlib"

# Use expect to automate the interactive prompts
expect << 'EOF'
spawn sudo pecl install memcached
expect "libmemcached directory"
send "/opt/homebrew/opt/libmemcached\r"
expect "zlib directory"
send "/opt/homebrew/opt/zlib\r"
expect "use system fastlz"
send "no\r"
expect "enable igbinary serializer"
send "no\r"
expect "enable msgpack serializer"
send "no\r"
expect "enable json serializer"
send "no\r"
expect "enable server protocol"
send "no\r"
expect "enable sasl"
send "yes\r"
expect "enable sessions"
send "yes\r"
expect eof
EOF

echo "Installation completed!"
