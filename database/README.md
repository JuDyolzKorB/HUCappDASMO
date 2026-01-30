# MySQL Database Setup Instructions

## Step 1: Import Database Schema

1. Open **phpMyAdmin** in your browser (usually at `http://localhost/phpmyadmin`)
2. Click on **Databases** in the top menu
3. Select the database `hucappdb` (or create it if it doesn't exist)
4. Click on the **Import** tab
5. Click **Choose File** and select: `database/schema.sql`
6. Click **Go** at the bottom to import

This will create all 21 tables with proper relationships.

## Step 2: Configure Database Credentials

1. Open `includes/config.php`
2. Update the following if needed:
   ```php
   define('DB_USER', 'root');  // Your MySQL username
   define('DB_PASS', '');      // Your MySQL password
   ```
3. Save the file

## Step 3: Migrate Existing Data (Optional)

If you have existing data in JSON files:

1. Open your browser and navigate to:
   ```
   http://localhost/HUCappDASMO/database/migrate.php
   ```
2. The migration will run automatically and show progress
3. Review the results to ensure all data was migrated successfully

## Step 4: Test the Application

1. Navigate to your application: `http://localhost/HUCappDASMO`
2. Try logging in with existing credentials
3. Test creating requisitions, purchase orders, etc.
4. Verify data appears in phpMyAdmin

## Troubleshooting

### Connection Error
- Verify MySQL is running
- Check username/password in `includes/config.php`
- Ensure database `hucappdb` exists

### Import Error
- Make sure you selected the correct database before importing
- Check for any error messages in phpMyAdmin

### Migration Issues
- Check that JSON files exist in the `data/` folder
- Review error messages in the migration output
- Verify database tables were created successfully

## Database Structure

The database includes these main tables:
- **User** - System users
- **HealthCenters** - Health center locations
- **Warehouse** - Warehouse locations
- **Item** & **ItemType** - Inventory items
- **Supplier** - Supplier information
- **PurchaseOrder** & **PurchaseOrderItem** - Purchase orders
- **Receiving** & **ReceivingItem** - Received items
- **CentralInventoryBatch** - Inventory batches
- **Requisition** & **RequisitionItem** - Requisitions
- **Issuance** & **IssuanceItem** - Issued items
- **ApprovalLog** - Approval history
- **TransactionAuditLog** - Transaction logs
- **SecurityLog** - Security events

All tables have proper foreign key relationships and indexes for optimal performance.
