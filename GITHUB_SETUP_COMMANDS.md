# GitHub Setup Commands

After creating your GitHub repository, run these commands to connect it:

## Replace YOUR_USERNAME with your actual GitHub username

```bash
cd "/Users/filippopovic/Local Sites/testni-sajt/app/public/wp-content/plugins/woocommerce-vip-paketi"

# Add GitHub remote
git remote add origin https://github.com/YOUR_USERNAME/woocommerce-vip-paketi.git

# Push to GitHub
git push -u origin main

# Verify connection
git remote -v
```

## Alternative with SSH (if you have SSH keys set up):

```bash
# Add SSH remote instead
git remote add origin git@github.com:YOUR_USERNAME/woocommerce-vip-paketi.git

# Push to GitHub
git push -u origin main
```

## After successful push, you can:

```bash
# Check status
git status

# See commit history
git log --oneline

# See remote info
git remote show origin
```