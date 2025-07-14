import numpy as np
from sklearn.ensemble import RandomForestRegressor
import joblib

# Generate some sample data
np.random.seed(42)
X = np.random.rand(100, 5)  # 100 samples, 5 features
y = np.random.rand(100) * 100  # Random weights between 0 and 100

# Create and train a simple model
model = RandomForestRegressor(n_estimators=10, random_state=42)
model.fit(X, y)

# Save the model
joblib.dump(model, '../model/waste_prediction_model.joblib')
print("Model saved successfully!") 