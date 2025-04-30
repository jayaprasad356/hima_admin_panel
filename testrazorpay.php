<?php
// Your payment link
$payment_link = "https://razorpay.com/payment-link/plink_Pu2lLEEiTAzH5M";

// Redirect to the payment link
header("Location: $payment_link");
exit();
?>
