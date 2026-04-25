# RHUB App - RHIMBS Higher Institute Portal

A comprehensive web application for RHIMBS Higher Institute featuring an online fee payment portal and student marketplace.

## Features

### 1. Online Fee Payment Portal
- Pay fees using MTN Mobile Money or Orange Money (Cameroon)
- Track payment history and remaining balance
- Real-time payment status updates
- View fee breakdown by category

### 2. Student Marketplace
- List items for sale with images, descriptions, and prices
- Browse items by category
- Search functionality
- Peer-to-peer messaging between buyers and sellers
- Automatic item removal when marked as sold

### 3. User Management
- Student registration with matricule verification
- Secure login system
- Profile management
- Department and level tracking

## Installation Guide (XAMPP)

### Step 1: Install XAMPP
1. Download XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. Install XAMPP on your computer
3. Start Apache and MySQL from the XAMPP Control Panel

### Step 2: Setup the Project
1. Copy the entire `rhub_app` folder to `C:\xampp\htdocs\` (Windows) or `/Applications/XAMPP/htdocs/` (Mac)
2. Your project should be at `htdocs/rhub_app/`

### Step 3: Create the Database
1. Open your browser and go to `http://localhost/phpmyadmin`
2. Click on "New" to create a new database
3. Name it `rhub_database` and click "Create"
4. Select the `rhub_database` from the left sidebar
5. Click on the "Import" tab
6. Click "Choose File" and select `rhub_app/database/rhub_database.sql`
7. Click "Go" to import the database structure

### Step 4: Configure Database Connection
1. Open `rhub_app/config/database.php`
2. Update the database credentials if needed:
   ```php
   private $host = "localhost";
   private $db_name = "rhub_database";
   private $username = "root";
   private $password = "";  // Default XAMPP has no password
   ```

### Step 5: Create Upload Directories
Create these folders if they don't exist:
- `rhub_app/uploads/`
- `rhub_app/uploads/items/`
- `rhub_app/uploads/profiles/`

Make sure these folders have write permissions.

### Step 6: Access the Application
Open your browser and go to:
```
http://localhost/rhub_app/
```

## Default Test Account
After setting up, you can register a new account or use these test credentials (if you run the seed data):
- Email: student@rhimbs.edu
- Password: password123

## Project Structure

```
rhub_app/
├── api/                    # API endpoints
│   ├── marketplace.php     # Marketplace operations
│   ├── messages.php        # Messaging system
│   └── process-payment.php # Payment processing
├── assets/
│   ├── css/               # Stylesheets
│   │   ├── style.css      # Main styles
│   │   ├── auth.css       # Authentication pages
│   │   ├── dashboard.css  # Dashboard styles
│   │   ├── fees.css       # Fee portal styles
│   │   ├── marketplace.css# Marketplace styles
│   │   └── messages.css   # Messaging styles
│   ├── js/                # JavaScript files
│   │   ├── main.js        # Main scripts
│   │   ├── dashboard.js   # Dashboard scripts
│   │   ├── fees.js        # Fee payment scripts
│   │   ├── marketplace.js # Marketplace scripts
│   │   └── messages.js    # Messaging scripts
│   └── images/            # Image assets
├── config/
│   └── database.php       # Database configuration
├── database/
│   └── rhub_database.sql  # Database schema
├── includes/
│   └── sidebar.php        # Sidebar navigation
├── uploads/               # User uploaded files
│   ├── items/            # Marketplace item images
│   └── profiles/         # Profile pictures
├── index.php              # Landing page
├── login.php              # Login page
├── register.php           # Registration page
├── dashboard.php          # User dashboard
├── fees.php               # Fee payment portal
├── payment-history.php    # Payment history
├── marketplace.php        # Marketplace listing
├── sell-item.php          # List item for sale
├── my-items.php           # User's listed items
├── item-detail.php        # Item detail view
├── messages.php           # Messaging center
├── profile.php            # User profile
└── logout.php             # Logout handler
```

## Technologies Used
- **Frontend:** HTML5, CSS3, JavaScript (Vanilla)
- **Backend:** PHP 7.4+
- **Database:** MySQL 5.7+
- **Server:** Apache (XAMPP)

## Color Scheme
- **Primary (Brick Red):** #8B3A3A
- **Secondary (Cream White):** #FFF8F0
- **Accent:** #C85C5C

## Browser Support
- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## Payment Integration Note
The MTN Mobile Money and Orange Money integrations are simulated for demonstration purposes. For production use, you would need to:
1. Register for MTN MoMo API access at [MTN Developer Portal](https://momodeveloper.mtn.com/)
2. Register for Orange Money API at [Orange Developer Portal](https://developer.orange.com/)
3. Replace the simulated payment processing in `api/process-payment.php` with actual API calls

## Support
For support, contact RHIMBS IT Department or create an issue in the repository.

## License
This project is for educational purposes only. All rights reserved by RHIMBS Higher Institute.
