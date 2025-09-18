#!/bin/bash

# GitHub Push with Token
export GIT_ASKPASS="echo"
export GIT_USERNAME="beopop"
export GIT_PASSWORD="YOUR_GITHUB_TOKEN_HERE"

cd "/Users/filippopovic/Local Sites/testni-sajt/app/public/wp-content/plugins/woocommerce-vip-paketi"

echo "🚀 Pushing to GitHub with Personal Access Token..."

# Configure git to use token
git config --local credential.helper ""
git config --local credential.helper "!f() { echo username=beopop; echo password=YOUR_GITHUB_TOKEN_HERE; }; f"

# Push to GitHub
git push -u origin main

echo "✅ Push completed!"