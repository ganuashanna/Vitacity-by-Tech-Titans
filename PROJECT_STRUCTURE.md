# 📁 Project Structure

```
parksync-vitacity/
├── 📄 README.md                    # Main documentation
├── 📄 docker-compose.yml           # Docker orchestration
├── 🔧 start.sh                     # Quick start script
├── 🐍 demo.py                      # AI demo script
│
├── 📂 backend/                     # FastAPI Backend
│   ├── 📄 Dockerfile
│   ├── 📄 requirements.txt
│   ├── 📄 .env.example
│   │
│   └── 📂 app/
│       ├── 📄 __init__.py
│       ├── 📄 main.py              # Main FastAPI app + routes
│       │
│       ├── 📂 core/                # Core configuration
│       │   ├── 📄 __init__.py
│       │   ├── 📄 config.py        # Settings & env vars
│       │   └── 📄 database.py      # DB connection
│       │
│       └── 📂 services/            # Business logic
│           ├── 📄 __init__.py
│           └── 📄 ai_engine.py     # Urban Nervous System AI
│
├── 📂 database/                    # Database files
│   ├── 📄 schema.sql               # Complete database schema
│   └── 📄 seed.sql                 # Sample data
│
├── 📂 frontend/                    # Your React frontend
│   └── 📄 [Your existing React app]
│
└── 📂 docs/                        # Documentation
    └── 📄 API_TESTING.md           # API testing guide
```

---

## 📋 Key Files Explained

### Backend

**`app/main.py`** (Main Application)
- FastAPI app instance
- All API routes (zones, bookings, complaints, AI)
- WebSocket handler for real-time updates
- Error handlers
- CORS configuration

**`app/services/ai_engine.py`** (Urban Nervous System)
- `UrbanNervousSystem` class - the AI brain
- Methods:
  - `observe_current_state()` - Monitor system
  - `detect_patterns()` - Find historical patterns
  - `predict_congestion()` - Forecast congestion
  - `detect_anomalies()` - Find issues
  - `make_decision()` - Autonomous decisions
  - `execute_action()` - Execute decisions
  - `learn_from_data()` - Pattern learning

**`app/core/config.py`** (Configuration)
- Environment variable loading
- Settings validation
- Default values

**`app/core/database.py`** (Database)
- Async PostgreSQL connection
- Session management
- Connection pooling

### Database

**`database/schema.sql`**
- 17 tables covering:
  - Core: users, zones, parking_slots, vehicles
  - ParkSync: bookings, booking_history
  - CivicAssist: complaints, complaint_patterns
  - AI: ai_predictions, ai_actions, ai_learning_data
  - Alerts: alerts, notifications
  - Analytics: system_metrics
- 3 views for real-time data
- Triggers for auto-calculations
- Indexes for performance

**`database/seed.sql`**
- Sample users (admin, citizens, authority)
- 6 zones (Z1-Z6) with coordinates
- 350 parking slots across zones
- Sample bookings, complaints
- AI predictions & actions
- Learning data

### Configuration

**`.env.example`**
- All environment variables
- API keys (Claude AI, Razorpay, etc.)
- Feature flags
- Thresholds
- Database connection

**`docker-compose.yml`**
- PostgreSQL + PostGIS
- Redis for caching
- FastAPI backend
- Network configuration
- Volume mounts

### Scripts

**`start.sh`**
- Checks Docker installation
- Creates .env if missing
- Starts all services
- Validates health
- Shows helpful commands

**`demo.py`**
- Comprehensive demo of AI features
- Tests all API endpoints
- Shows predictions in action
- Demonstrates anomaly detection
- Example decision-making

---

## 🔄 Data Flow

### 1. Booking Flow
```
User → Frontend → POST /api/bookings
                     ↓
                   Backend validates
                     ↓
                   Insert to bookings table
                     ↓
                   Update slot state
                     ↓
                   Broadcast via WebSocket
                     ↓
                   AI observes pattern
```

### 2. Complaint Flow
```
User → Frontend → POST /api/complaints
                     ↓
                   AI categorizes
                     ↓
                   Insert to complaints table
                     ↓
                   Check complaint_patterns
                     ↓
                   If pattern detected → Create maintenance ticket
                     ↓
                   Broadcast via WebSocket
```

### 3. AI Prediction Flow
```
GET /api/ai/predict/congestion/Z1
            ↓
    Observe current state
            ↓
    Get historical patterns
            ↓
    Apply ML model
            ↓
    Check complaint impact
            ↓
    Calculate confidence
            ↓
    Store prediction
            ↓
    Return forecast
```

### 4. AI Decision Flow
```
POST /api/ai/decide {"zone_id": "Z1"}
            ↓
    Observe current state
            ↓
    Detect patterns
            ↓
    Detect anomalies
            ↓
    Apply rules (if occupancy > 85% → alert)
            ↓
    Consult Claude AI (if enabled)
            ↓
    Generate decisions
            ↓
    Execute actions:
      - Send alerts
      - Adjust pricing
      - Create tickets
      - Route traffic
            ↓
    Log actions
            ↓
    Broadcast updates
```

---

## 🗄️ Database Tables Overview

### Core Tables
| Table | Purpose | Key Fields |
|-------|---------|------------|
| `users` | User accounts | email, role, reward_points, tier |
| `zones` | Parking zones | name, lat/lng, total_slots, price |
| `parking_slots` | Individual slots | zone_id, state, type, sensor_status |
| `vehicles` | Registered vehicles | license_plate, user_id, qr_code |

### ParkSync Tables
| Table | Purpose | Key Fields |
|-------|---------|------------|
| `bookings` | Active/past bookings | user_id, zone_id, slot_id, status, cost |
| `booking_history` | Historical data for AI | start_time, duration, congestion_level |

### CivicAssist Tables
| Table | Purpose | Key Fields |
|-------|---------|------------|
| `complaints` | User complaints | type, priority, status, requires_maintenance |
| `complaint_patterns` | Recurring issues | occurrence_count, pattern_detected |

### AI Tables
| Table | Purpose | Key Fields |
|-------|---------|------------|
| `ai_predictions` | Forecasts | prediction_type, confidence, predicted_occupancy |
| `ai_actions` | Executed actions | action_type, status, impact_metrics |
| `ai_learning_data` | Aggregated patterns | day_of_week, hour, avg_occupancy, pattern_strength |

### System Tables
| Table | Purpose | Key Fields |
|-------|---------|------------|
| `alerts` | System alerts | alert_type, severity, status |
| `notifications` | User notifications | user_id, type, is_read |
| `system_metrics` | Daily analytics | bookings, revenue, prediction_accuracy |

---

## 🔌 API Endpoints Summary

### Zones
- `GET /api/zones` - List all zones
- `GET /api/zones/{id}` - Zone details
- `GET /api/zones/{id}/slots` - Zone slots

### Bookings
- `POST /api/bookings` - Create booking
- `GET /api/bookings` - List bookings (filterable)

### Complaints
- `POST /api/complaints` - File complaint
- `GET /api/complaints` - List complaints (filterable)

### AI
- `GET /api/ai/predict/congestion/{zone_id}` - Predict congestion
- `GET /api/ai/anomalies/{zone_id}` - Detect anomalies
- `POST /api/ai/decide` - Make AI decision
- `GET /api/ai/state/{zone_id}` - Get system state
- `POST /api/ai/learn` - Trigger learning

### Analytics
- `GET /api/analytics/dashboard` - Dashboard metrics

### WebSocket
- `WS /ws` - Real-time updates

---

## 🧩 Component Integration

### Frontend ↔ Backend
```javascript
// Fetch zones
const zones = await fetch('http://localhost:8000/api/zones')
  .then(r => r.json());

// WebSocket for real-time
const ws = new WebSocket('ws://localhost:8000/ws');
ws.onmessage = (e) => {
  const update = JSON.parse(e.data);
  // Update UI
};
```

### Backend ↔ Database
```python
# Using async SQLAlchemy
async with AsyncSession() as session:
    result = await session.execute(text("SELECT * FROM zones"))
    zones = result.fetchall()
```

### Backend ↔ AI Engine
```python
from app.services.ai_engine import uns

# Make prediction
prediction = await uns.predict_congestion(db, zone_id, hours_ahead=2)

# Make decision
decision = await uns.make_decision(db, context)
```

---

## 📊 Performance Considerations

### Database
- Indexes on frequently queried fields
- Connection pooling (20 connections)
- Async queries for non-blocking I/O

### Caching
- Redis caches zone data (5 min TTL)
- Prediction results cached
- User sessions cached

### WebSocket
- Max 1000 concurrent connections
- Heartbeat every 30 seconds
- Automatic reconnection

### AI
- Rule-based decisions run in <100ms
- ML predictions run in <500ms
- Claude API calls: ~2-3 seconds (only for complex decisions)
- Learning aggregation: daily background job

---

## 🔐 Security

### Authentication
- JWT tokens (30 min expiry)
- Refresh tokens (7 days)
- Password hashing with bcrypt

### Database
- Prepared statements (SQL injection protection)
- Connection pooling limits
- Row-level security (future)

### API
- CORS enabled for specified origins
- Rate limiting (60 req/min)
- Input validation with Pydantic

### Deployment
- SSL/TLS in production
- Environment variables for secrets
- Database backups

---

## 🚀 Deployment Architecture

```
                    ┌─────────────┐
                    │   Internet  │
                    └──────┬──────┘
                           │
                    ┌──────▼──────┐
                    │  Load Bal   │
                    │   (Nginx)   │
                    └──────┬──────┘
                           │
        ┌──────────────────┼──────────────────┐
        │                  │                  │
   ┌────▼────┐       ┌────▼────┐       ┌────▼────┐
   │ Backend │       │ Backend │       │ Backend │
   │Instance1│       │Instance2│       │Instance3│
   └────┬────┘       └────┬────┘       └────┬────┘
        │                  │                  │
        └──────────────────┼──────────────────┘
                           │
        ┌──────────────────┼──────────────────┐
        │                  │                  │
   ┌────▼────┐       ┌────▼────┐       ┌────▼────┐
   │PostgreSQL│      │  Redis  │       │  S3     │
   │  (RDS)   │      │(ElastiCache)│   │(Uploads)│
   └──────────┘      └──────────┘      └─────────┘
```

---

## 📝 Adding New Features

### New API Endpoint
1. Add route in `app/main.py`
2. Add database queries if needed
3. Test with curl/Postman
4. Update API_TESTING.md

### New AI Feature
1. Add method to `UrbanNervousSystem` class
2. Add database tables/columns if needed
3. Test with demo.py
4. Document in README

### New Database Table
1. Add to `database/schema.sql`
2. Add seed data to `database/seed.sql`
3. Recreate database: `docker-compose down -v && docker-compose up`
4. Update models if using ORM

---

**Questions? Check the main README.md or API_TESTING.md!**
