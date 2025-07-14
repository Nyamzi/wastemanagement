from flask import Flask, request, jsonify
import joblib
import numpy as np
from datetime import datetime, timedelta
import sqlite3

app = Flask(__name__)

# Load the trained model
model = joblib.load('waste_predictor.pkl')

# Waste type encoding mapping
WASTE_TYPE_ENCODING = {
    "Plastics": 1,
    "Organic": 2,
    "Glass": 3,
    "Metals": 4,
    "Paper": 5,
    "Textiles": 6,
    "Electronics": 7,
    "Batteries": 8,
    "Chemicals": 9,
    "Rubber": 10,
    "Wood": 11,
    "Food Waste": 12,
    "Garden Waste": 13,
    "Medical Waste": 14,
    "Construction Debris": 15,
    "Oil and Grease": 16,
    "Expired Goods": 17,
    "Others": 18
}

def get_area_id(area_name):
    try:
        conn = sqlite3.connect('wastemanagement.db')
        cursor = conn.cursor()
        cursor.execute("SELECT id FROM areas WHERE area_name = ?", (area_name,))
        result = cursor.fetchone()
        conn.close()
        return result[0] if result else 1  # Default to 1 if not found
    except:
        return 1  # Default to 1 if error

@app.route('/predict', methods=['POST'])
def predict():
    try:
        # Get data from POST request
        data = request.get_json()
        
        # Extract features in the correct order
        features = [
            data['day_of_week'],
            data['week_of_year'],
            data['month'],
            data['area_id'],
            WASTE_TYPE_ENCODING[data['waste_type']]
        ]
        
        # Convert to numpy array and reshape
        features_array = np.array(features).reshape(1, -1)
        
        # Make prediction
        prediction = model.predict(features_array)
        
        # Return prediction
        return jsonify({
            'predicted_weight': float(prediction[0])
        })
    except Exception as e:
        return jsonify({'error': str(e)}), 400

@app.route('/future_predictions', methods=['POST'])
def future_predictions():
    try:
        data = request.get_json()
        area_name = data['area_name']
        waste_type = data['waste_type']
        days_ahead = int(data.get('days_ahead', 7))  # Default to 7 days if not specified
        
        predictions = []
        current_date = datetime.now()
        
        for i in range(days_ahead):
            future_date = current_date + timedelta(days=i)
            day_of_week = future_date.weekday() + 1  # 1-7
            week_of_year = future_date.isocalendar()[1]
            month = future_date.month
            
            features = [
                day_of_week,
                week_of_year,
                month,
                area_name,
                WASTE_TYPE_ENCODING[waste_type]
            ]
            
            features_array = np.array(features).reshape(1, -1)
            prediction = model.predict(features_array)
            
            predictions.append({
                'date': future_date.strftime('%Y-%m-%d'),
                'day_of_week': day_of_week,
                'predicted_weight': float(prediction[0])
            })
        
        return jsonify({
            'area_name': area_name,
            'waste_type': waste_type,
            'predictions': predictions
        })
    except Exception as e:
        return jsonify({'error': str(e)}), 400

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000) 