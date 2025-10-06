# How to Get GitHub Credentials - Simple Steps

## 🔐 Method: Personal Access Token (Easiest)

### Step 1: Go to GitHub Token Settings
1. Open your browser
2. Go to: **https://github.com/settings/tokens**
3. Sign in with your `naveenraj44125-creator` account if not already signed in

### Step 2: Create New Token
1. Click **"Generate new token"**
2. Select **"Generate new token (classic)"**

### Step 3: Fill Token Details
```
Note: Lightsail Demo Project
Expiration: 90 days (or No expiration if you prefer)

Scopes (check these boxes):
✅ repo (Full control of private repositories)
✅ workflow (Update GitHub Action workflows)
```

### Step 4: Generate and Copy Token
1. Click **"Generate token"** at the bottom
2. **IMMEDIATELY COPY THE TOKEN** (it looks like: `ghp_xxxxxxxxxxxxxxxxxxxx`)
3. **Save it somewhere safe** - you won't see it again!

### Step 5: Configure Git (Run these commands)
```bash
# Set your GitHub username
git config --global user.name "naveenraj44125-creator"

# Set your email (use the email associated with your GitHub account)
git config --global user.email "your-github-email@example.com"
```

## 🚀 What to Give Me

Once you have the token, provide me with:

1. **Personal Access Token**: `ghp_xxxxxxxxxxxxxxxxxxxx` (the token you copied)
2. **Your GitHub Email**: The email address associated with your GitHub account

## 📋 Example

```
Token: ghp_1234567890abcdefghijklmnopqrstuvwxyz
Email: your-email@example.com
```

## 🔒 Security Note

- This token acts as your password
- Keep it private and secure
- You can revoke it anytime at https://github.com/settings/tokens

## ⚡ Quick Links

- **Create Token**: https://github.com/settings/tokens
- **Your Profile**: https://github.com/naveenraj44125-creator

Once you provide the token and email, I'll configure everything and push the code to your GitHub repository!
