# 📊 AMP PROMPT: Transform Feature Menu into Executive Dashboard

## **CURRENT PROBLEM**
The current homepage at `index.php` is just **6 buttons linking to features** - it's a navigation menu, not a dashboard. We need a **real-time executive dashboard** that shows:
- Key business metrics at a glance
- Live data previews from each feature  
- Quick actions and drill-down capabilities
- Modern dashboard UI with charts and widgets

## **TRANSFORMATION REQUEST**

**Transform the current "6-button feature menu" into a comprehensive executive dashboard that manufacturing distributors would actually use daily.**

### **DASHBOARD REQUIREMENTS**

#### **1. 📈 Executive KPIs Section (Top)**
```
Revenue This Month: $125,847
Orders in Pipeline: 23 orders  
Low Stock Alerts: 5 products
Active Quotes: 12 pending
```

#### **2. 🎛️ Feature Preview Widgets**
Instead of just buttons, create **interactive widgets** that show real data:

**📱 Product Catalog Widget**:
- Top 5 selling products this month
- Quick search bar
- "View Full Catalog" button

**📊 Pipeline Widget**:
- Mini Kanban with order counts per stage
- Recent order updates
- "View Full Pipeline" button

**📦 Inventory Widget**:
- Stock level alerts (red/yellow/green)
- Products needing reorder
- "Check All Inventory" button

**📄 Quote Builder Widget**:
- Recent quotes created
- Pending approvals
- Quick "New Quote" button

**🔍 Search Widget**:
- Global search across products/orders/customers
- Recent searches
- Search suggestions

**👥 User Activity Widget**:
- Recent user logins
- Role-based activity summary
- Team performance metrics

#### **3. 🚀 Quick Actions Bar**
Top navigation with:
- Global search
- New quote button
- Customer lookup
- Settings
- User profile

#### **4. 📱 Mobile-First Design**
- Responsive grid layout
- Touch-friendly interactions
- Collapsible widgets on mobile

## **TECHNICAL SPECIFICATIONS**

### **Data Sources**
- Pull live data from existing `mfg_*` database tables
- Use existing API endpoints (`/Api/v1/manufacturing/`)
- Implement caching for performance
- Real-time updates via AJAX

### **UI Framework**
- Keep existing Tailwind CSS
- Add Chart.js for visualizations
- Implement CSS Grid for dashboard layout
- Maintain mobile responsiveness

### **Performance Requirements**
- Dashboard load time: <2 seconds
- Auto-refresh key metrics every 30 seconds
- Lazy load non-critical widgets
- Cache API responses

## **FILE TO MODIFY**
**Primary**: `index.php` (current 6-button menu)
**Additional**: May need new dashboard API endpoints

## **INSPIRATION**
Think **Salesforce Lightning**, **HubSpot Dashboard**, or **Monday.com** - modern, data-rich, actionable dashboards that give users everything they need on one screen.

## **SUCCESS CRITERIA**
1. ✅ Users can see business health at a glance
2. ✅ Each feature has a meaningful preview widget
3. ✅ Quick actions are easily accessible  
4. ✅ Mobile experience is excellent
5. ✅ Data loads fast and updates automatically
6. ✅ Maintains professional manufacturing industry look

## **PRIORITY**
**HIGH** - This dashboard will be the first thing reviewers see and sets the tone for the entire application. 