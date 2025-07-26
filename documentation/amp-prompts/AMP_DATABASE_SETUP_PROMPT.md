# AMP Code Prompt: Database Setup for Manufacturing Module

## TASK OVERVIEW
You are working on a SuiteCRM Enterprise Legacy Modernization project. Your primary focus is implementing the database foundation for the manufacturing module's mobile-responsive product catalog with client-specific pricing.

## PRIMARY OBJECTIVES
1. **Database Schema Creation**: Design and implement the core manufacturing tables
2. **Sample Data Population**: Create realistic test data for development and demo purposes  
3. **Checklist Updates**: Mark each completed task with an ❌ in the REMAINING_TASKS_CHECKLIST.md file

## SPECIFIC TASKS TO COMPLETE

### Database Tables to Create:

1. **`mfg_products` table** - Core product information with:
   - SKU (unique identifier)
   - Product name and description
   - Base pricing fields
   - Inventory/stock fields
   - Product categories and specifications
   - Images and documentation links

2. **`mfg_pricing_tiers` table** - Customer pricing levels:
   - Tier types: Retail, Wholesale, OEM, etc.
   - Discount percentages or multipliers
   - Minimum order quantities
   - Effective date ranges

3. **`mfg_client_contracts` table** - Negotiated customer pricing:
   - Client-specific pricing overrides
   - Contract terms and conditions
   - Volume-based pricing tiers
   - Contract expiration dates

4. **Sample Data Population**:
   - Minimum 50+ diverse products with realistic manufacturing data
   - Multiple pricing tiers with logical pricing structure
   - Sample client contracts showing different pricing levels
   - Products across various categories (steel, aluminum, fasteners, etc.)

## TECHNICAL REQUIREMENTS

### Database Standards:
- Follow SuiteCRM database naming conventions
- Include proper indexes for performance
- Add foreign key relationships where appropriate
- Include created_by, date_entered, date_modified fields for audit trail
- Add soft delete capability (deleted field)

### Integration Points:
- Ensure compatibility with existing SuiteCRM Accounts module
- Connect with existing user/contact management
- Consider future integration with inventory systems
- Plan for API endpoint access

### Sample Data Quality:
- Use realistic manufacturing product names and SKUs
- Include varied product categories and materials
- Create logical pricing structures (Retail > Wholesale > OEM)
- Generate realistic stock levels and reorder points

## COMPLETION PROCESS

After completing each task, you MUST:

1. **Update the checklist** - Replace `- [ ]` with `- [x]` for each completed item in REMAINING_TASKS_CHECKLIST.md under:
   ```
   ### **🛒 Feature 1: Mobile-Responsive Product Catalog with Client-Specific Pricing (10 Points)**
   - [ ] **Database Setup**
     - [ ] Create `mfg_products` table with SKU, pricing, inventory fields
     - [ ] Create `mfg_pricing_tiers` table (Retail, Wholesale, OEM, etc.)
     - [ ] Create `mfg_client_contracts` table for negotiated pricing
     - [ ] Populate sample product data (50+ products minimum)
   ```

2. **Validate your work** by:
   - Running test queries to verify data integrity
   - Checking table structures match requirements
   - Ensuring sample data is realistic and comprehensive
   - Confirming SuiteCRM compatibility

3. **Document any assumptions** or design decisions made during implementation

## SUCCESS CRITERIA
- ✅ All 4 database setup tasks marked complete with ❌ in checklist
- ✅ Tables created with proper SuiteCRM structure and relationships  
- ✅ 50+ products with realistic manufacturing data populated
- ✅ Multiple pricing tiers and client contracts established
- ✅ Database schema supports mobile-responsive catalog requirements

## CONTEXT AWARENESS
- This is part of a 7-day enterprise modernization project
- Focus on creating a solid foundation for mobile product catalog
- Consider performance implications for mobile devices
- Plan for future real-time inventory integration
- Ensure scalability for enterprise-level product catalogs

Begin with table creation, then populate sample data, and finally update the checklist with ❌ marks for each completed item. 