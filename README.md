# bsky-followback
Automated script to follow back users on Bluesky

This simple script automatically follows back anyone who follows you on Bluesky — perfect for building community and supporting mutual growth.

## 💡 Features

- Automatically follows back new followers
- Checks and follows up to 10 users per run
- Optional support for custom PDS addresses
- Safe login using an App Password

---

## 🚀 Getting Started

### 1. Create an App Password

To use the script, you'll need a Bluesky App Password.

1. Go to [bsky.app/settings/app-passwords](https://bsky.app/settings/app-passwords)
2. Create a new App Password
3. Copy the generated password in the format: `xxxx-xxxx-xxxx-xxxx`

⚠️ **Important:** App passwords give limited access and are safer than using your main password. Never share your main Bluesky credentials.

### 2. Let your browser run the script
It will automaticly followback everyone that follows you, following the latest followers first, then work its way down to earlier follows.
The Script refreshes every 60 seconds and no handle / app password is saved on the webserver.

### 3. Close your browser when your done
Simply close the browser tab to stop the script. Come back any time.

Functional script : https://garbaek.it/bsky-followback/

Make by https://bsky.app/profile/garbaek.dk / Garbæk IT https://garbaek.dk 
