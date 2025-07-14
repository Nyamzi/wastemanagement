import pandas as pd
from prophet import Prophet
import joblib  # Optional for saving the model

# Load the data
data = pd.read_csv("pickup_data.csv")

# Inspect column names
print("Column names:", data.columns)

# Ensure the correct date and weight columns are used
try:
    # Convert pickup_date to datetime and rename columns for Prophet
    data['pickup_date'] = pd.to_datetime(data['pickup_date'])
    # Selecting the relevant columns for Prophet and renaming them as per Prophet's requirement
    df = data[['pickup_date', 'weight']].rename(columns={'pickup_date': 'ds', 'weight': 'y'})
except KeyError as e:
    print(f"Error: {e}. Please check your CSV file for the correct column names.")
    exit()

# Initialize and train the Prophet model
model = Prophet()
model.fit(df)

# Save the trained model (optional)
joblib.dump(model, 'waste_forecast_model.pkl')  # Save the model for later use

# Future prediction (forecast for the next 365 days)
future = model.make_future_dataframe(periods=365)  # Adjust periods for the desired prediction horizon
forecast = model.predict(future)

# Save the forecast to CSV
forecast.to_csv("forecast_output.csv", index=False)

# Optional: Print confirmation
print("Prediction saved to 'forecast_output.csv' and model saved as 'waste_forecast_model.pkl'")
