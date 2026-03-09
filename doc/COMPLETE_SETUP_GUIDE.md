# 🚀 VITACITY - Complete Setup Guide for Beginners

**Team Tech Titan | SAMVED 2026 | Dr. Babasaheb Ambedkar Marathwada University**

---

## 🎯 What You're Building

**VITACITY** - Smart Parking Urban Nervous System for Solapur with:
- ✅ PHP Backend (runs on Vercel - FREE!)
- ✅ Supabase PostgreSQL Database (FREE 500MB!)
- ✅ 4-Agent AI System (Observer, Memory, Decision, Action)
- ✅ Complete Frontend (HTML/CSS/JavaScript)
- ✅ Real-time predictions & anomaly detection
- ✅ **NO CODING NEEDED - Just copy & paste!**

---

## 📋 What You Need (All FREE!)

1. **GitHub Account** - To store your code
2. **Vercel Account** - To host your website (free forever)
3. **Supabase Account** - For database (free 500MB)
4. **VS Code** - To view/edit files (optional)

**Time needed:** 30-45 minutes total

---

## 🎓 STEP-BY-STEP GUIDE FOR ABSOLUTE BEGINNERS

### PART 1: Setup Accounts (10 minutes)

#### Step 1.1: Create GitHub Account

1. Go to: https://github.com
2. Click "Sign up"
3. Enter your email (use your college email!)
4. Create password
5. Choose username (example: `ganesh-techitan`)
6. Verify email
7. ✅ Done!

#### Step 1.2: Create Vercel Account

1. Go to: https://vercel.com
2. Click "Sign Up"
3. Click "Continue with GitHub" (easier!)
4. Authorize Vercel to access GitHub
5. ✅ Done!

#### Step 1.3: Create Supabase Account

1. Go to: https://supabase.com
2. Click "Start your project"
3. Click "Sign in with GitHub"
4. Authorize Supabase
5. ✅ Done!

---

### PART 2: Setup Database (10 minutes)

#### Step 2.1: Create New Project in Supabase

1. In Supabase dashboard, click **"New Project"**
2. Fill in:
   - **Name:** `vitacity-solapur`
   - **Database Password:** Create a STRONG password (save it!)
   - **Region:** Choose closest to India (Singapore or Mumbai if available)
3. Click **"Create new project"**
4. Wait 2-3 minutes for database to initialize
5. ✅ Project created!

#### Step 2.2: Run Database Schema

1. Click **"SQL Editor"** in left sidebar
2. Click **"+ New query"**
3. **Copy the ENTIRE database schema** from `database/schema.sql` file
4. **Paste** into the SQL editor
5. Click **"Run"** (bottom right)
6. Wait 10-20 seconds
7. You should see "Success. No rows returned"
8. ✅ Database tables created!

#### Step 2.3: Add Sample Data

1. Still in SQL Editor, click **"+ New query"**
2. **Copy the ENTIRE seed data** from `database/seed.sql` file
3. **Paste** into the SQL editor
4. Click **"Run"**
5. You should see "Success" messages
6. ✅ Sample data loaded!

#### Step 2.4: Get Database Connection Details

1. Click **"Settings"** (gear icon in left sidebar)
2. Click **"Database"**
3. Scroll to **"Connection string"**
4. Find these details (you'll need them later):
   - **Host:** `db.xxxxxx.supabase.co`
   - **Database name:** `postgres`
   - **Port:** `5432`
   - **User:** `postgres`
   - **Password:** (the password you created)
5. **SAVE THESE!** Write them down or screenshot

---

### PART 3: Upload Code to GitHub (10 minutes)

#### Step 3.1: Download VITACITY Project

1. Download the `vitacity-php` folder I created for you
2. Save it to your **Desktop** or **Documents**
3. Unzip if it's a ZIP file

#### Step 3.2: Create GitHub Repository

1. Go to: https://github.com/new
2. Fill in:
   - **Repository name:** `vitacity-solapur`
   - **Description:** `Smart Parking System for Solapur - SAMVED 2026`
   - **Public** (keep it public - it's required for free Vercel)
3. ✅ **DO NOT** check "Add README"
4. Click **"Create repository"**
5. ✅ Repository created!

#### Step 3.3: Upload Files to GitHub

**Option A: Using Web Interface (Easiest for beginners)**

1. On your new repository page, click **"uploading an existing file"**
2. **Drag and drop** ALL files from `vitacity-php` folder
3. Wait for files to upload
4. Scroll down, add commit message: "Initial VITACITY commit"
5. Click **"Commit changes"**
6. ✅ Code uploaded!

**Option B: Using GitHub Desktop (If you prefer)**

1. Download GitHub Desktop: https://desktop.github.com/
2. Install and sign in with your GitHub account
3. Clone your repository
4. Copy all `vitacity-php` files into the cloned folder
5. Commit and push

---

### PART 4: Deploy to Vercel (10 minutes)

#### Step 4.1: Connect GitHub to Vercel

1. Go to: https://vercel.com/dashboard
2. Click **"Add New..."** → **"Project"**
3. Find your `vitacity-solapur` repository
4. Click **"Import"**

#### Step 4.2: Configure Project

1. **Framework Preset:** Select "Other"
2. **Root Directory:** Leave as `.` (default)
3. **Build Command:** Leave empty
4. **Output Directory:** Leave as `public`
5. Click **"Deploy"**
6. Wait 30-60 seconds
7. ✅ Deployment complete!

#### Step 4.3: Add Environment Variables

**IMPORTANT:** Your app won't work without this step!

1. Go to your project dashboard in Vercel
2. Click **"Settings"**
3. Click **"Environment Variables"**
4. Add these variables one by one:

**Variable 1:**
- **Name:** `DB_HOST`
- **Value:** Your Supabase host (e.g., `db.xxxxxx.supabase.co`)
- Click **"Add"**

**Variable 2:**
- **Name:** `DB_NAME`
- **Value:** `postgres`
- Click **"Add"**

**Variable 3:**
- **Name:** `DB_USER`
- **Value:** `postgres`
- Click **"Add"**

**Variable 4:**
- **Name:** `DB_PASS`
- **Value:** Your Supabase database password
- Click **"Add"**

**Variable 5:**
- **Name:** `DB_PORT`
- **Value:** `5432`
- Click **"Add"**

5. Click **"Redeploy"** → **"Redeploy"** to apply changes
6. ✅ Environment variables set!

---

### PART 5: Test Your System! (5 minutes)

#### Step 5.1: Get Your Website URL

1. In Vercel dashboard, find your project
2. You'll see a URL like: `vitacity-solapur.vercel.app`
3. Click on it
4. ✅ Your website opens!

#### Step 5.2: Test API Endpoints

Open these URLs in your browser (replace `YOUR-URL` with your actual Vercel URL):

**1. Test Zones:**
```
https://YOUR-URL.vercel.app/api/zones.php
```
You should see JSON data with all parking zones!

**2. Test AI Prediction:**
```
https://YOUR-URL.vercel.app/api/ai/predict.php?zone_id=Z1&hours_ahead=2
```
You should see congestion prediction with 4-agent analysis!

**3. Test Anomaly Detection:**
```
https://YOUR-URL.vercel.app/api/ai/anomalies.php?zone_id=Z1
```
You should see detected anomalies!

**4. Open Frontend:**
```
https://YOUR-URL.vercel.app
```
You should see the VITACITY dashboard!

✅ **If all tests pass, YOUR SYSTEM IS LIVE!** 🎉

---

## 🎬 Demo for Judges

### Show the 4-Agent AI System:

1. **Open prediction URL**
2. **Point to the JSON response:**

```json
{
  "agent_analysis": {
    "observer": "Current: 72.5% occupied",
    "memory": "Historical: 85% typical",
    "decision": "High congestion predicted",
    "action_needed": true
  }
}
```

3. **Explain:**
   - 👁️ **Observer:** Watches real-time parking states
   - 🧠 **Memory:** Recalls "Friday 6PM always busy"
   - 🎯 **Decision:** Predicts 89% occupancy in 2 hours
   - ⚡ **Action:** Auto-sends alerts, adjusts pricing

---

## 🐛 Troubleshooting

### Problem: "Database connection failed"

**Solution:**
1. Check environment variables in Vercel
2. Make sure database password is correct
3. Verify Supabase project is running (green status)

### Problem: "404 Not Found"

**Solution:**
1. Check file paths - use `.php` extension
2. Example: `/api/zones.php` not `/api/zones`

### Problem: "No data showing"

**Solution:**
1. Re-run seed.sql in Supabase SQL Editor
2. Check browser console for errors
3. Verify API calls are working

### Problem: "Blank page"

**Solution:**
1. Check Vercel deployment logs
2. Look for PHP errors
3. Ensure all files uploaded correctly

---

## 📝 Customization for Your Team

### Add Solapur Zones

**Edit:** `database/seed.sql`

**Replace zones with:**
```sql
INSERT INTO zones (id, name, latitude, longitude, total_slots, base_price_per_hour) VALUES
('Z1', 'Siddheshwar Peth Market', 17.6599, 75.9064, 80, 20.00),
('Z2', 'Civil Hospital Area', 17.6714, 75.9101, 60, 25.00),
('Z3', 'Railway Station', 17.6714, 75.9104, 70, 30.00),
('Z4', 'Navi Peth Market', 17.6734, 75.9123, 50, 20.00),
('Z5', 'Ashwini Hospital', 17.6654, 75.8989, 45, 25.00),
('Z6', 'Bus Stand Area', 17.6799, 75.9134, 55, 22.00);
```

**Then:** Re-run in Supabase SQL Editor

### Update Team Info

**Edit:** Frontend `index.html`

**Change:**
```html
<h1>VITACITY</h1>
<p>Team Tech Titan - SAMVED 2026</p>
```

---

## ✅ Final Checklist Before Presentation

- [ ] GitHub repository created and public
- [ ] Supabase database running with data
- [ ] Vercel deployed and live
- [ ] All API endpoints tested
- [ ] Frontend displaying data
- [ ] AI predictions working
- [ ] Anomaly detection working
- [ ] Team info updated
- [ ] Solapur zones added
- [ ] Demo script practiced

---

## 🎓 What to Tell Judges

**The Problem:**
"Solapur faces daily predictable congestion - same places, same times. But the city has no intelligence to prevent it."

**Your Solution:**
"VITACITY uses a 4-agent AI that learns from parking AND complaint patterns to predict congestion BEFORE it happens."

**The Innovation:**
"Unlike static parking systems:
1. **Observer Agent** watches real-time data
2. **Memory Agent** remembers 'Friday 6PM Civil Hospital always full'
3. **Decision Agent** predicts 89% occupancy 2 hours ahead
4. **Action Agent** auto-sends alerts, adjusts pricing, creates maintenance tickets"

**The Impact:**
"Reduces congestion, saves citizens time, builds urban intelligence that gets smarter daily."

---

## 🚀 You're Live!

**Your VITACITY system is now:**
- ✅ Running on Vercel (free forever!)
- ✅ Using Supabase database (free 500MB)
- ✅ Making AI predictions
- ✅ Accessible worldwide
- ✅ Ready to demo!

**Your URL:** `https://vitacity-solapur.vercel.app`

**Share it with:**
- Judges
- Mentors
- Teammates
- Anyone!

---

## 📞 Quick Reference

**Vercel Dashboard:** https://vercel.com/dashboard
**Supabase Dashboard:** https://app.supabase.com
**GitHub Repo:** https://github.com/YOUR-USERNAME/vitacity-solapur
**Live Site:** https://YOUR-PROJECT.vercel.app

---

**Good luck at SAMVED-2026, Team Tech Titan! 🎉**

**You've built a production-ready smart city system in under an hour!**

Questions? Check the troubleshooting section or contact your mentor Dr. Pravin Yannawar.
