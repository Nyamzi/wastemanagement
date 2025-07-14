import pandas as pd
import pickle
from sklearn.ensemble import RandomForestRegressor

# Load your dataset
df = pd.read_csv("pickup_data.csv")  # change this to your real CSV or data source

# Make sure pickup_date is datetime
df['pickup_date'] = pd.to_datetime(df['pickup_date'])

# Extract features
df['day_of_week'] = df['pickup_date'].dt.dayofweek
df['week_of_year'] = df['pickup_date'].dt.isocalendar().week.astype(int)
df['month'] = df['pickup_date'].dt.month

# Encode pickup type (you can adjust the logic based on your types)
df['waste_type_encoded'] = df['waste_type'].astype('category').cat.codes

# Define features and target
features = ['day_of_week', 'week_of_year', 'month', 'area_id', 'waste_type_encoded']
target = 'weight'

X = df[features]
y = df[target]

# Train model
model = RandomForestRegressor()
model.fit(X, y)

# ✅ Save the actual model object
with open('waste_predictor.pkl', 'wb') as f:
    pickle.dump(model, f)

print("✅ Model trained and saved successfully!")
