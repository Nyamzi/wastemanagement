# Waste Management Project

This is a waste management system that includes both a machine learning backend (Python) and a PHP-based web dashboard for administration and reporting.

## Features
- Waste pickup prediction using machine learning
- Admin dashboard for managing companies, pickups, and payments
- User and company dashboards
- PDF report generation
- Data uploads and management

## Project Structure
- `ml/` — Python machine learning backend (Flask API, model training, prediction)
- `php/` — PHP web application (admin dashboard, user/company management, uploads)
- `app.py` — Main Python entry point

## Setup Instructions

### 1. Clone the Repository
```bash
git clone https://github.com/Nyamzi/wastemanagement.git
cd wastemanagement
```

### 2. Python Backend Setup
```bash
cd ml
python -m venv venv
venv\Scripts\activate  # On Windows
pip install -r requirements.txt
python app.py  # or flask run
```

### 3. PHP Frontend Setup
- Place the `php/` directory in your web server's root (e.g., XAMPP `htdocs` or WAMP `www`).
- Ensure PHP and a web server (Apache/Nginx) are installed.
- Configure your database connection in `php/dbconnect.php`.

### 4. Database
- Import your database schema (not included here) into MySQL or your preferred database.

## Usage
- Access the PHP dashboard via your web browser (e.g., http://localhost/php/).
- The Python backend provides API endpoints for predictions and data processing.

## Contributing
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

## License
[MIT](LICENSE) (or specify your license here) 