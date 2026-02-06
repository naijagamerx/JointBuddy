# Current Tasks - CannaBuddy.shop

**Last Updated**: 2025-12-08
**Priority Level**: 🔴 CRITICAL
**Status**: Active

---

## 🚨 IMMEDIATE CRITICAL TASKS (This Week)

### Task 1: Decide Production System
**Priority**: 🔴 CRITICAL
**Assigned To**: Development Team Lead
**Deadline**: 2025-12-10 (2 days)
**Status**: ⏳ PENDING

#### Description
The codebase has two complete, separate systems. We must decide which is production:
- **Option A**: Standalone PHP System (Root directory)
- **Option B**: CodeIgniter 4 System (app/ directory)

#### Decision Criteria
- [ ] Feature completeness comparison
- [ ] Code quality assessment
- [ ] Performance considerations
- [ ] Maintenance burden analysis
- [ ] Team familiarity with each system
- [ ] Deployment complexity

#### Current Analysis
**Recommendation**: Standalone PHP System

**Rationale**:
- ✅ More complete admin system (20+ pages vs 1 CI4 controller)
- ✅ More developed user system (25+ subdirectories)
- ✅ File-based routing works with shared hosting
- ✅ Documented as "primary" in CLAUDE.md
- ✅ Admin UI is more polished

#### Action Items
1. **Review both systems** feature-by-feature
2. **Document pros/cons** of each system
3. **Make final decision** (Team Lead)
4. **Communicate decision** to entire team
5. **Update all documentation** to reflect decision

---

### Task 2: Audit
**Priority**: System Features 🔴 CRITICAL
**Assigned To**: Senior**: 2025 Developer
**Deadline-12-11 (3 days)
**Status**: ⏳ PENDING

#### Description
Conduct comprehensive feature audit of both systems to ensure nothing is lost in consolidation.

#### Audit Checklist

##### Standalone PHP System
- [ ] Homepage functionality (slider, featured products, deals)
- [ ] Admin panel features (20+ pages)
  - [ ] Analytics
  - [ ] Delivery methods
  - [ ] Login system
  - [ ] Orders management
  - [ ] Payment methods
  - [ ] Products CRUD + variations
  - [ ] SEO settings
  - [ ] System settings (appearance, currency, email, notifications)
  - [ ] Slider management
  - [ ] Users management
- [ ] User system features (25+ subdirectories)
  - [ ] Address book
  - [ ] Dashboard (5 versions - need to check differences)
  - [ ] Login (4 versions - need to check differences)
  - [ ] Orders with tracking
  - [ ] Personal details
  - [ ] Reviews
  - [ ] Security settings
  - [ ] All other subdirectories
- [ ] Shop system
- [ ] Cart and checkout
- [ ] PayFast integration
- [ ] Database layer (PDO)

##### CodeIgniter 4 System
- [ ] Homepage functionality
- [ ] Admin controller features
- [ ] User controller features (JWT auth)
- [ ] Shop controller features
- [ ] Product controller features
- [ ] Cart controller features
- [ ] Checkout controller features
- [ ] PayFast controller features
- [ ] All models (4 models)
- [ ] Database layer (MySQLi)
- [ ] Views structure

#### Deliverable
Create comprehensive feature matrix comparing both systems.

---

### Task 3: Plan Consolidation Strategy
**Priority**: 🔴 CRITICAL
**Assigned To**: Tech Lead
**Deadline**: 2025-12-12 (4 days)
**Status**: ⏳ PENDING

#### Description
Create detailed plan for consolidating to single system without data loss or feature regression.

#### Strategy Components

##### Option A: Standalone PHP (Recommended)
1. **Keep**: All standalone system files
2. **Audit**: CI4 system for unique features
3. **Port**: Any unique CI4 features to standalone
4. **Delete**: `app/` directory and CI4 dependencies
5. **Update**: Documentation

##### Option B: CodeIgniter 4
1. **Keep**: All CI4 system files
2. **Audit**: Standalone system for unique features
3. **Port**: Any unique standalone features to CI4
4. **Delete**: Standalone system files
5. **Update**: Documentation

#### Migration Plan Template
```
Phase 1: Preparation
- [ ] Feature audit complete
- [ ] Decision made
- [ ] Migration plan documented
- [ ] Team briefed

Phase 2: Port Features
- [ ] Identify features to port
- [ ] Port features to chosen system
- [ ] Test ported features
- [ ] Verify data integrity

Phase 3: Cleanup
- [ ] Remove deprecated system
- [ ] Clean up duplicate files
- [ ] Update authentication
- [ ] Update database connections

Phase 4: Testing
- [ ] Full system test
- [ ] User acceptance testing
- [ ] Performance testing
- [ ] Security audit

Phase 5: Deployment
- [ ] Deploy to staging
- [ ] Final testing
- [ ] Deploy to production
- [ ] Monitor for issues
```

---

## 📋 HIGH PRIORITY TASKS (Week 2)

### Task 4: Clean Up Duplicate Files
**Priority**: 🔴 HIGH
**Assigned To**: Developer
**Deadline**: 2025-12-15 (1 week)
**Status**: ⏳ PENDING

#### Description
Remove duplicate versions of files in standalone system.

#### Files to Clean Up

##### user/dashboard/ Directory
Currently has 5 versions:
1. `enhanced_dashboard.php`
2. `fixed_dashboard.php`
3. `index.php`
4. `main_dashboard.php`
5. `my-account-test.php`
6. `my-account.php`
7. `router.php`

**Action**:
- [ ] Review each version
- [ ] Identify best version (likely `index.php`)
- [ ] Keep 1, delete 4
- [ ] Ensure no functionality lost

##### user/login/ Directory
Currently has 4 versions:
1. `index.php`
2. `index_new.php`
3. `redirect_fix.php`
4. `simple_login.php`

**Action**:
- [ ] Review each version
- [ ] Identify best version (likely latest with fixes)
- [ ] Keep 1, delete 3
- [ ] Test login flow thoroughly

#### General Cleanup
- [ ] Search for other duplicate files
- [ ] Remove unused/obsolete files
- [ ] Clean up temporary/test files

---

### Task 5: Complete Incomplete Features
**Priority**: 🟡 MEDIUM-HIGH
**Assigned To**: Developer
**Deadline**: 2025-12-18 (10 days)
**Status**: ⏳ PENDING

#### Description
Complete features marked as incomplete in project_status.md.

##### User Dashboard Features
1. **Address Book** (`user/address-book/`)
   - [ ] Review current implementation
   - [ ] Complete CRUD operations
   - [ ] Test functionality
   - [ ] Add to user dashboard

2. **Personal Details** (`user/personal-details/`)
   - [ ] Review current implementation
   - [ ] Complete profile editing
   - [ ] Test functionality
   - [ ] Add to user dashboard

3. **My Reviews** (`user/reviews/`)
   - [ ] Review current implementation
   - [ ] Complete review system
   - [ ] Test functionality
   - [ ] Add to user dashboard

##### Admin Panel Features
1. **Product Reviews** (`admin/products/reviews.php`)
   - [ ] Review basic implementation
   - [ ] Enhance review management
   - [ ] Add moderation features
   - [ ] Test functionality

---

### Task 6: Standardize Authentication
**Priority**: 🔴 HIGH
**Assigned To**: Senior Developer
**Deadline**: 2025-12-16 (8 days)
**Status**: ⏳ PENDING

#### Description
Consolidate to single authentication system after choosing production system.

#### Current Authentication Systems

##### Standalone PHP (Canonical)
- **Admin Auth**: AdminAuth class in `includes/database.php`
- **User Auth**: UserAuth class in `includes/database.php`
- **Method**: password_verify() + PHP sessions
- **Features**: Login attempt limiting, account locking
    
##### CodeIgniter 4 (Legacy, Not Deployed)
- **Admin Auth**: Admin controller with JWT (legacy only)
- **User Auth**: User controller with JWT (legacy only)
- **Method**: JWT tokens + CodeIgniter sessions (deprecated)
- **Features**: Token refresh, API-style auth (not used in current runtime)
    
#### Standardization Plan
1. **Chosen authentication method**: Standalone `AdminAuth` / `UserAuth` (canonical)
2. **Audit both systems** for auth-related code and ensure CI4 entry points are not deployed
3. **Maintain single implementation** in `includes/database.php`
4. **Test all login flows** in the standalone system (admin and user)
5. **Update documentation** to clearly mark CI4 auth as legacy

---

### Task 7: Database Layer Standardization
**Priority**: 🟡 MEDIUM
**Assigned To**: Developer
**Deadline**: 2025-12-17 (9 days)
**Status**: ⏳ PENDING

#### Description
Standardize database layer after choosing production system.

#### Current Database Layers

##### Standalone PHP
- **Driver**: PDO
- **Config**: `includes/database.php`
- **Features**: Prepared statements, error handling

##### CodeIgniter 4
- **Driver**: MySQLi
- **Config**: `app/Config/Database.php`
- **Features**: CI4 Query Builder

#### Standardization Plan
1. **Choose database driver** (recommend keeping PDO - more modern)
2. **Update database config** in chosen system
3. **Test all database operations**
4. **Update documentation**

---

## 📊 ONGOING TASKS

### Task 8: Update Documentation
**Priority**: 🟡 MEDIUM
**Assigned To**: Technical Writer
**Deadline**: 2025-12-20 (12 days)
**Status**: ⏳ PENDING

#### Description
Update all project documentation to reflect consolidation and current state.

#### Documents to Update
- [ ] **CLAUDE.md** - Main guidance document
- [ ] **PROJECT_REQUIREMENTS_DOCUMENT.md** - PRD
- [ ] **agent.md** - Technical documentation
- [ ] **project_status.md** - Status (just updated)
- [ ] **README.md** - If exists
- [ ] Any other documentation files

#### Content Updates Needed
- [ ] Remove references to dual system
- [ ] Update architecture diagrams
- [ ] Update feature lists
- [ ] Update deployment instructions
- [ ] Update development setup instructions

---

### Task 9: Testing & Quality Assurance
**Priority**: 🔴 HIGH
**Assigned To**: QA Engineer
**Deadline**: 2025-12-18 (10 days)
**Status**: ⏳ PENDING

#### Description
Comprehensive testing of consolidated system.

#### Testing Checklist

##### Functional Testing
- [ ] Homepage (slider, featured products, deals)
- [ ] Admin panel (all 20+ pages)
- [ ] User system (all 25+ subdirectories)
- [ ] Shop system
- [ ] Cart and checkout
- [ ] PayFast integration

##### Security Testing
- [ ] Authentication flows
- [ ] Authorization (admin vs user access)
- [ ] SQL injection prevention
- [ ] XSS prevention
- [ ] CSRF protection
- [ ] Session security

##### Performance Testing
- [ ] Page load times
- [ ] Database query optimization
- [ ] Image optimization
- [ ] CDN usage (Tailwind, Alpine.js)

##### Compatibility Testing
- [ ] Desktop browsers (Chrome, Firefox, Safari, Edge)
- [ ] Mobile devices (iOS, Android)
- [ ] PHP 8.3.1 compatibility
- [ ] MySQL 5.7.24 compatibility

---

### Task 10: PayFast Integration Completion
**Priority**: 🟡 MEDIUM
**Assigned To**: Developer
**Deadline**: 2025-12-22 (14 days)
**Status**: ⏳ PENDING

#### Description
Complete PayFast integration for production use.

#### Current Status
- PayFast integration prepared in both systems
- "Redirect Only" mode implemented
- Sandbox → Live transition ready

#### Tasks
- [ ] Review PayFast implementation
- [ ] Complete payment flow
- [ ] Add webhook handling
- [ ] Test in sandbox mode
- [ ] Prepare for live mode transition
- [ ] Add payment confirmation emails
- [ ] Test complete checkout flow

---

## 📅 TIMELINE SUMMARY

### Week 1 (Dec 8-14, 2025)
- [ ] **Dec 8-10**: Decide production system
- [ ] **Dec 8-11**: Audit system features
- [ ] **Dec 8-12**: Plan consolidation strategy
- [ ] **Dec 8-14**: Begin feature porting

### Week 2 (Dec 15-21, 2025)
- [ ] **Dec 15**: Clean up duplicate files
- [ ] **Dec 16**: Standardize authentication
- [ ] **Dec 17**: Standardize database layer
- [ ] **Dec 18**: Complete incomplete features
- [ ] **Dec 18**: Begin testing
- [ ] **Dec 20**: Update documentation

### Week 3 (Dec 22-28, 2025)
- [ ] **Dec 22**: Complete PayFast integration
- [ ] **Dec 23-25**: Holiday break (if applicable)
- [ ] **Dec 26-28**: Final testing and deployment

---

## 🎯 SUCCESS CRITERIA

### Phase 1 Success (Week 1)
- [ ] Production system decided
- [ ] Consolidation plan documented
- [ ] Team aligned on approach
- [ ] Feature audit complete

### Phase 2 Success (Week 2)
- [ ] Single system operational
- [ ] All features ported and working
- [ ] Duplicate files removed
- [ ] Authentication standardized
- [ ] Database layer standardized

### Phase 3 Success (Week 3)
- [ ] All testing complete
- [ ] Documentation updated
- [ ] Production deployment ready
- [ ] Team trained on new structure

---

## 📞 TEAM ASSIGNMENTS

| Task | Assignee | Backup |
|------|----------|--------|
| System Decision | Team Lead | Senior Developer |
| Feature Audit | Senior Developer | Developer |
| Consolidation Plan | Tech Lead | Senior Developer |
| File Cleanup | Developer | Junior Developer |
| Complete Features | Developer | Junior Developer |
| Auth Standardization | Senior Developer | Tech Lead |
| DB Standardization | Developer | Senior Developer |
| Documentation | Technical Writer | Tech Lead |
| Testing | QA Engineer | Developer |
| PayFast Integration | Developer | Senior Developer |

---

## 🚫 CRITICAL REMINDERS

### Business Rules (NEVER Violate)
- ❌ **NEVER mention "3D printing"** - Business secret
- ❌ **NO 3D models or viewers** - Real product photos only
- ❌ **NO localStorage** - Server-side sessions only
- ❌ **NO client-side cart** - Must be server-side with MySQL

### Technical Requirements
- ✅ **Use PDO with prepared statements** (if keeping standalone)
- ✅ **Server-side sessions** for cart and state management
- ✅ **Bcrypt password hashing** for all passwords
- ✅ **CSRF protection** via session tokens

---

## 📈 METRICS TO TRACK

### Progress Metrics
- [ ] Tasks completed per week
- [ ] Features ported successfully
- [ ] Files cleaned up
- [ ] Documentation pages updated

### Quality Metrics
- [ ] Test coverage percentage
- [ ] Bugs found during testing
- [ ] Performance benchmarks
- [ ] Security scan results

### Business Metrics
- [ ] System consolidation timeline
- [ ] Developer productivity
- [ ] Deployment success rate
- [ ] User satisfaction (post-deployment)

---

## 🔄 REVIEW SCHEDULE

### Daily Standups
- **Time**: 9:00 AM SAST
- **Duration**: 15 minutes
- **Agenda**: Progress update, blockers, next 24h tasks

### Weekly Reviews
- **Time**: Friday 4:00 PM SAST
- **Duration**: 1 hour
- **Agenda**: Week summary, metrics review, next week planning

### Milestone Reviews
- **Phase 1**: After system decision (Dec 12)
- **Phase 2**: After consolidation (Dec 19)
- **Phase 3**: After testing (Dec 26)

---

**Document Owner**: Development Team
**Next Update**: Daily during standups
**Status**: ACTIVE - HIGH PRIORITY
