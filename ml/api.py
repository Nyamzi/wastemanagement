from flask import Flask, request, jsonify
import joblib
import pandas as pd
from prophet import Prophet

app = Flask(__name__)

# Load the trained Prophet model
model = joblib.load('waste_forecast_model.pkl')

@app.route('/predict', methods=['POST'])
def predict():
    try:
        # Parse JSON data from POST request
        input_data = request.get_json()

        # Validate input
        if not input_data or 'pickup_date' not in input_data or 'weight' not in input_data:
            return jsonify({"error": "Invalid input data. Please provide 'pickup_date' and 'weight'."}), 400

        # Convert input data to DataFrame
        df = pd.DataFrame(input_data)

        # Ensure correct format for Prophet model
        if 'pickup_date' in df.columns:
            df['pickup_date'] = pd.to_datetime(df['pickup_date'])  # Convert dates
            df = df.rename(columns={'pickup_date': 'ds', 'weight': 'y'})  # Rename for Prophet compatibility
        else:
            return jsonify({"error": "'pickup_date' column is missing."}), 400

        # Perform prediction
        forecast = model.predict(df)

        # Format and return the response
        result = forecast[['ds', 'yhat', 'yhat_lower', 'yhat_upper']].to_dict(orient='records')
        return jsonify(result)

    except Exception as e:
        return jsonify({"error": str(e)}), 500

if __name__ == '__main__':
    app.run(debug=True, host='0.0.0.0', port=5000)
