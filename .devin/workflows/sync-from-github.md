---
description: Sync (pull) latest changes from GitHub remote
---

# Sync from GitHub Workflow

## Prerequisites
- Git installed and configured
- Repository already cloned at `/opt/lampp/htdocs/panglong`
- Remote `origin` set to `https://github.com/82080038/panglong.git`

## Steps

1. **Check current status**
   ```bash
   git status
   git remote -v
   ```

2. **Handle local changes**
   - If there are uncommitted changes, choose one:
     - **Stash:** `git stash` (save temporarily)
     - **Discard:** `git checkout .` (discard all local changes)
     - **Commit:** `git add -A && git commit -m "WIP: local changes before sync"`

3. **Pull from GitHub**
   ```bash
   git pull origin master
   ```

4. **Restore stashed changes (if stashed)**
   ```bash
   git stash pop
   ```
   - If conflicts occur on binary files (e.g., `database/database.sqlite`):
     ```bash
     git checkout --theirs database/database.sqlite
     git add database/database.sqlite
     git checkout -- .
     git reset HEAD -- .
     git checkout -- .
     git stash drop
     ```

5. **Verify sync**
   ```bash
   git log --oneline -5
   git status
   ```

6. **Set database permissions** (if database.sqlite was updated)
   ```bash
   chmod 666 database/database.sqlite
   chmod 777 database/
   ```

## Notes
- Branch: `master`
- Binary files (database.sqlite, screenshots) cannot be merged — always take one side
- After sync, verify the app works by visiting http://localhost/panglong/frontend/
- Default users: admin, manager1, kasir1, gudang1, accounting1, supervisor1 (password: password123)
