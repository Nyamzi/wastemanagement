from flask import Flask, request, jsonify
import joblib
import numpy as np
from flask_cors import CORS
import pandas as pd
from datetime import datetime, timedelta

app = Flask(__name__)
CORS(app)

# Load the trained model
try:
    model = joblib.load('model/waste_prediction_model.joblib')
except:
    print("Error: Model file not found. Please ensure the model is saved in the model directory.")

@app.route('/predict', methods=['POST'])
def predict():
    try:
        # Get data from request
        data = request.get_json()
        
        # Extract features
        features = [
            float(data['day_of_week']),
            float(data['week_of_year']),
            float(data['month']),
            float(data['area_id']),
            float(data['pickup_type_encoded'])
        ]
        
        # Convert to numpy array and reshape for prediction
        features_array = np.array(features).reshape(1, -1)
        
        # Make prediction
        prediction = model.predict(features_array)
        
        # Return prediction
        return jsonify({
            'status': 'success',
            'predicted_weight': float(prediction[0])
        })
        
    except Exception as e:
        return jsonify({
            'status': 'error',
            'error': str(e)
        }), 400

@app.route('/predict_future', methods=['POST'])
def predict_future():
    try:
        data = request.get_json()
        area_id = int(data['area_id'])
        pickup_type_encoded = int(data['pickup_type_encoded'])
        days_ahead = int(data.get('days_ahead', 30))  # Default to 30 days if not specified
        
        # Generate future dates
        current_date = datetime.now()
        predictions = []
        
        for i in range(days_ahead):
            future_date = current_date + timedelta(days=i)
            features = [
                float(future_date.weekday()),  # day_of_week
                float(future_date.isocalendar()[1]),  # week_of_year
                float(future_date.month),  # month
                float(area_id),
                float(pickup_type_encoded)
            ]
            
            features_array = np.array(features).reshape(1, -1)
            prediction = model.predict(features_array)
            
            predictions.append({
                'date': future_date.strftime('%Y-%m-%d'),
                'predicted_weight': float(prediction[0])
            })
        
        return jsonify({
            'status': 'success',
            'predictions': predictions
        })
        
    except Exception as e:
        return jsonify({
            'status': 'error',
            'error': str(e)
        }), 400

if __name__ == '__main__':
    app.run(debug=True, port=5000)