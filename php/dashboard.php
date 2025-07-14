        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Make Payment</h5>
                    </div>
                    <div class="card-body">
                        <form action="process_payment.php" method="POST">
                            <div class="mb-3">
                                <label for="amount" class="form-label">Amount (UGX)</label>
                                <input type="number" class="form-control" id="amount" name="amount" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Pay Now</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Payment History</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Fetch payment history for the logged-in user
                                    $payment_sql = "SELECT * FROM payments WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
                                    $payment_stmt = $conn->prepare($payment_sql);
                                    $payment_stmt->bind_param("i", $user_id);
                                    $payment_stmt->execute();
                                    $payment_result = $payment_stmt->get_result();

                                    if ($payment_result->num_rows > 0) {
                                        while ($payment = $payment_result->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td>" . date('M d, Y', strtotime($payment['created_at'])) . "</td>";
                                            echo "<td>UGX " . number_format($payment['amount']) . "</td>";
                                            echo "<td><span class='badge " . ($payment['status'] == 'completed' ? 'bg-success' : 'bg-warning') . "'>" . ucfirst($payment['status']) . "</span></td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='3' class='text-center'>No payment history found</td></tr>";
                                    }
                                    $payment_stmt->close();
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div> 