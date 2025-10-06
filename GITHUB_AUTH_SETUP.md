# GitHub Authentication Setup

Yes, you need GitHub credentials to push code to GitHub. Here are the authentication options:

## 🔐 Authentication Methods

### Option 1: Personal Access Token (Recommended)
This is the most secure and modern way to authenticate with GitHub.

#### Step 1: Create Personal Access Token
1. Go to: **https://github.com/settings/tokens**
2. Click **"Generate new token"** → **"Generate new token (classic)"**
3. Fill in:
   - **Note**: `Lightsail Demo Project`
   - **Expiration**: `90 days` (or your preference)
   - **Scopes**: Check these boxes:
     - ✅ `repo` (Full control of private repositories)
     - ✅ `workflow` (Update GitHub Action workflows)
     - ✅ `write:packages` (Upload packages to GitHub Package Registry)

4. Click **"Generate token"**
5. **COPY THE TOKEN** immediately (you won't see it again!)

#### Step 2: Configure Git with Token
```bash
# Set your GitHub username
git config --global user.name "naveenraj44125-creator"

# Set your email (use your GitHub email)
git config --global user.email "your-email@example.com"

# When prompted for password during push, use the token instead
```

#### Step 3: Push with Token
When the script tries to push and asks for credentials:
- **Username**: `naveenraj44125-creator`
- **Password**: `[paste your personal access token here]`

### Option 2: SSH Key (Alternative)
If you prefer SSH authentication:

#### Step 1: Generate SSH Key
```bash
# Generate new SSH key
ssh-keygen -t ed25519 -C "your-email@example.com"

# Start SSH agent
eval "$(ssh-agent -s)"

# Add SSH key to agent
ssh-add ~/.ssh/id_ed25519
```

#### Step 2: Add SSH Key to GitHub
1. Copy your public key:
   ```bash
   cat ~/.ssh/id_ed25519.pub
   ```
2. Go to: **https://github.com/settings/keys**
3. Click **"New SSH key"**
4. Paste your public key
5. Click **"Add SSH key"**

#### Step 3: Use SSH URL
Change the remote URL to SSH:
```bash
git remote set-url origin git@github.com:naveenraj44125-creator/github-actions-lightsail-demo.git
```

### Option 3: GitHub CLI (Easiest)
Install and authenticate with GitHub CLI:

```bash
# Install GitHub CLI (if not installed)
# macOS: brew install gh
# Or download from: https://cli.github.com/

# Authenticate
gh auth login

# Follow the prompts:
# - Choose GitHub.com
# - Choose HTTPS
# - Authenticate via web browser
```

## 🚀 Recommended Approach

**For this demo, I recommend Option 1 (Personal Access Token)**:

1. **Create token**: https://github.com/settings/tokens
2. **Configure Git**:
   ```bash
   git config --global user.name "naveenraj44125-creator"
   git config --global user.email "your-github-email@example.com"
   ```
3. **Continue the setup script** - when it asks for credentials:
   - Username: `naveenraj44125-creator`
   - Password: `[your-personal-access-token]`

## 🔧 Quick Setup Commands

Run these before continuing the setup script:

```bash
# Set your Git identity
git config --global user.name "naveenraj44125-creator"
git config --global user.email "your-email@example.com"

# Optional: Store credentials temporarily (for convenience)
git config --global credential.helper 'cache --timeout=3600'
```

## 🚨 Security Notes

- **Never share** your personal access token
- **Use tokens** instead of passwords
- **Set expiration dates** on tokens
- **Revoke tokens** when no longer needed

## 📋 What the Script Will Do

Once authenticated, the setup script will:
1. Add all files to Git
2. Create initial commit
3. Push to your GitHub repository
4. Show success message with repository URL

## 🔗 Useful Links

- **Personal Access Tokens**: https://github.com/settings/tokens
- **SSH Keys**: https://github.com/settings/keys
- **GitHub CLI**: https://cli.github.com/
- **Git Credential Helper**: https://git-scm.com/docs/git-credential-store

Choose your preferred authentication method and set it up before continuing with the repository creation!
