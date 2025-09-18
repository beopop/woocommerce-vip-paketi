#!/bin/bash

# GitHub Push Script for WooCommerce VIP Paketi Plugin
echo "🚀 Pushing to GitHub..."

cd "/Users/filippopovic/Local Sites/testni-sajt/app/public/wp-content/plugins/woocommerce-vip-paketi"

echo "📍 Current directory: $(pwd)"
echo "📊 Git status:"
git status --short

echo ""
echo "🔄 Pushing to GitHub..."
echo "If prompted for credentials:"
echo "Username: beopop"
echo "Password: [Use your Personal Access Token from GitHub Settings]"
echo ""

git push -u origin main

if [ $? -eq 0 ]; then
    echo "✅ Successfully pushed to GitHub!"
    echo "🌐 View at: https://github.com/beopop/woocommerce-vip-paketi"
else
    echo "❌ Push failed. Check your credentials."
    echo "💡 Need help? Get Personal Access Token from:"
    echo "   https://github.com/settings/tokens"
fi