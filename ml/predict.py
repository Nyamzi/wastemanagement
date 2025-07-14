import sys
import joblib
import pandas as pd
from datetime import datetime

# Simulate input (you can pass via command line later)
area_id = 1
waste_type = "plastics"
pickup_date = "2025-11-14"  # Format: YYYY-MM-DD

# Extract features from date
pickup_date = pd.to_datetime(pickup_date)
dayofweek = pickup_date.dayofweek
month = pickup_date.month
day = pickup_date.day

# Create input data in the same format as training
input_data = pd.DataFrame([{
    "area_id": area_id,
    "waste_type": waste_type,
    "dayofweek": dayofweek,
    "month": month,
    "day": day
}])

# Load model
model = joblib.load("model_weight.pkl")

# Make prediction
prediction = model.predict(input_data)
print(f"Predicted waste weight: {prediction[0]:.2f} kg")
