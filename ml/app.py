from flask import Flask, jsonify, request
import joblib
import pandas as pd
from prophet import Prophet

app = Flask(__name__)

# Load the trained model
model = joblib.load('waste_forecast_model.pkl')  # Adjust the path as needed

@app.route('/forecast', methods=['POST'])
def forecast():
    try:
        # Get input data (in JSON format)
        input_data = request.get_json()

        # Convert input data into a DataFrame (assuming the data contains 'pickup_date' and 'weight' columns)
        new_data = pd.DataFrame(input_data)

        # Preprocess the data (convert the date to datetime and adjust columns for Prophet)
        new_data['pickup_date'] = pd.to_datetime(new_data['pickup_date'])
        df_new = new_data[['pickup_date', 'weight']].rename(columns={'pickup_date': 'ds', 'weight': 'y'})

        # Make predictions using the trained model
        forecast_new = model.predict(df_new)

        # Prepare the response (return the prediction with 'ds' and 'yhat')
        forecast_json = forecast_new[['ds', 'yhat', 'yhat_lower', 'yhat_upper']].to_dict(orient='records')
        return jsonify(forecast_json)

    except Exception as e:
        # Return error if something goes wrong
        return jsonify({'error': str(e)}), 400

if __name__ == '__main__':
    # Run the Flask server
    app.run(debug=True)
