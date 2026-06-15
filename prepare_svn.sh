#!/bin/bash

# Exit immediately if a command exits with a non-zero status
set -e

echo "=== WordPress.org SVN Repository Preparation Script ==="

# 1. Check if svn is installed
if ! command -v svn &> /dev/null; then
    echo "Error: Subversion (svn) is not installed."
    echo "Please install it by running:"
    echo "  sudo apt-get update && sudo apt-get install -y subversion"
    exit 1
fi

SVN_URL="https://plugins.svn.wordpress.org/tropipay-gateway-for-woocommerce"
SVN_DIR="svn-checkout"
PLUGIN_SRC="tropipay-gateway-for-woocommerce"
VERSION="5.0.0"

# 2. Checkout SVN repository if not already done
if [ ! -d "$SVN_DIR" ]; then
    echo "Checking out SVN repository from $SVN_URL..."
    svn co "$SVN_URL" "$SVN_DIR"
else
    echo "SVN repository already checked out in $SVN_DIR. Updating..."
    cd "$SVN_DIR"
    svn update
    cd ..
fi

# 3. Clean up existing trunk and tags/VERSION to ensure fresh copy
echo "Preparing trunk and tags/$VERSION directories..."
mkdir -p "$SVN_DIR/trunk"
mkdir -p "$SVN_DIR/tags/$VERSION"
mkdir -p "$SVN_DIR/assets"

# Delete existing contents (if any) in SVN trunk and tag
rm -rf "$SVN_DIR"/trunk/*
rm -rf "$SVN_DIR"/tags/"$VERSION"/*

# 4. Copy plugin files to trunk and tags/VERSION
echo "Copying plugin files to trunk..."
cp -RL "$PLUGIN_SRC"/* "$SVN_DIR/trunk/"
rm -f "$SVN_DIR/trunk/"*.doc

echo "Copying plugin files to tags/$VERSION..."
cp -RL "$PLUGIN_SRC"/* "$SVN_DIR/tags/$VERSION/"
rm -f "$SVN_DIR/tags/$VERSION/"*.doc

# 5. Add files to SVN
echo "Adding new files to SVN tracking..."
cd "$SVN_DIR"

# Force add all files
svn add --force trunk
svn add --force tags/"$VERSION"
svn add --force assets

# Handle deleted/missing files (just in case this is run on a modified repo)
svn status | grep '!' | awk '{print $2}' | xargs -r svn rm

echo ""
echo "=== Preparation Complete ==="
echo "Plugin files have been copied and prepared in the '$SVN_DIR' directory."
echo ""
echo "Next steps:"
echo "1. If you have assets (banners, icons, screenshots) to upload to the WordPress.org directory, place them in the '$SVN_DIR/assets/' directory now."
echo "   - Banner names: banner-772x250.png, banner-1544x500.png"
echo "   - Icon names: icon-128x128.png, icon-256x256.png"
echo "   - Screenshots: screenshot-1.png, screenshot-2.png, etc."
echo "2. Check the SVN status to verify the changes:"
echo "   cd $SVN_DIR"
echo "   svn status"
echo "3. When ready, commit your changes using your WordPress.org credentials:"
echo "   svn commit --username tropipaydev -m \"Release version $VERSION\""
echo ""
