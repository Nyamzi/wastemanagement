from flask import Flask, request, jsonify
import pickle
import numpy as np

app = Flask(__name__)

# Load the trained model
with open('waste_predictor.pkl', 'rb') as f:
    model = pickle.load(f)

@app.route('/')
def home():
    return "Waste Prediction Model API is running."

@app.route('/predict', methods=['POST'])
def predict():
    data = request.get_json()

    try:
        # Extract features from the POSTed JSON
        features = [
            data['day_of_week'],
            data['week_of_year'],
            data['month'],
            data['area_id'],
            data['pickup_type_encoded']
        ]
        prediction = model.predict([features])[0]

        return jsonify({'predicted_weight': round(prediction, 2)})

    except Exception as e:
        return jsonify({'error': str(e)})

if __name__ == '__main__':
    app.run(debug=True)