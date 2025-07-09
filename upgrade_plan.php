<?php
require_once 'config.php';
require_once 'includes/header.php';

// Redirect to login if not logged in
if (!is_logged_in()) {
    redirect('auth/login.php');
}

// Get all active plans
$query = "SELECT * FROM plans WHERE is_active = 1 ORDER BY price ASC";
$result = mysqli_query($conn, $query);
$plans = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Convert features from JSON to array
    if (!empty($row['features'])) {
        $row['features_array'] = json_decode($row['features'], true);
    } else {
        $row['features_array'] = [];
    }
    $plans[] = $row;
}

// Get current user's plan
$current_plan = $user['plan_type'];

// Handle plan upgrade form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upgrade_plan'])) {
    $selected_plan = $_POST['plan_type'];
    
    // Here you would implement the payment processing logic
    // For now, we'll just show a success message
    set_flash_message('success', 'Your plan has been upgraded successfully!');
    redirect('index.php');
}

// Set page title
$page_title = "Upgrade Plan";
?>

<div class="container upgrade-plan-container py-5">
    <div class="row mb-4">
        <div class="col-md-12 text-center">
            <h1 class="mb-2"><i class="fas fa-crown mr-3 text-warning"></i>Upgrade Your Plan</h1>
            <p class="text-muted">Choose the perfect plan for your data analysis needs</p>
        </div>
    </div>
    
    <!-- Plan Selection Cards -->
    <div class="row mb-5">
        <?php foreach ($plans as $plan): ?>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card plan-card h-100 <?php echo $plan['plan_type'] === $current_plan ? 'current-plan' : ''; ?>">
                <?php if ($plan['plan_type'] === $current_plan): ?>
                <div class="current-plan-badge">
                    <i class="fas fa-check-circle"></i> Current Plan
                </div>
                <?php endif; ?>
                
                <?php if ($plan['plan_type'] === 'pro'): ?>
                <div class="popular-badge">
                    <i class="fas fa-star"></i> Most Popular
                </div>
                <?php endif; ?>
                
                <div class="card-header text-center py-4">
                    <h3 class="plan-name mb-0"><?php echo htmlspecialchars($plan['plan_name']); ?></h3>
                </div>
                <div class="card-body d-flex flex-column">
                    <div class="plan-price text-center mb-4">
                        <span class="currency">$</span>
                        <span class="amount"><?php echo number_format($plan['price'], 2); ?></span>
                        <span class="period">/month</span>
                    </div>
                    
                    <div class="plan-tokens text-center mb-4">
                        <div class="token-icon">
                            <i class="fas fa-coins"></i>
                        </div>
                        <div class="token-amount">
                            <?php echo number_format($plan['monthly_tokens']); ?>
                        </div>
                        <div class="token-label">tokens per month</div>
                    </div>
                    
                    <ul class="plan-features">
                        <?php 
                        // Default features based on plan type if no specific features are set
                        $default_features = [
                            'free' => [
                                'Basic data analysis',
                                'CSV & Excel file support',
                                'Standard visualizations',
                                'Email support'
                            ],
                            'starter' => [
                                'Advanced data analysis',
                                'Priority processing',
                                'Extended visualizations',
                                'Priority email support'
                            ],
                            'pro' => [
                                'Premium data analysis',
                                'Advanced AI models',
                                'Custom visualizations',
                                'Priority support'
                              
                            ],
                            'teams' => [
                                'Enterprise data analysis',
                                'Dedicated AI resources',
                                'Custom reporting',
                                'Dedicated support',
                                'Team collaboration'
                            
                            ]
                        ];
                        
                        $features_to_display = !empty($plan['features_array']) ? $plan['features_array'] : $default_features[$plan['plan_type']];
                        
                        foreach ($features_to_display as $feature):
                        ?>
                        <li><i class="fas fa-check-circle mr-2"></i> <?php echo htmlspecialchars($feature); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <div class="mt-auto text-center">
                        <?php if ($plan['plan_type'] === $current_plan): ?>
                        <button class="btn btn-outline-primary btn-block" disabled>
                            <i class="fas fa-check mr-2"></i>Current Plan
                        </button>
                        <?php elseif ($plan['plan_type'] === 'free'): ?>
                        <button class="btn btn-outline-primary btn-block" disabled>
                            Free Plan
                        </button>
                        <?php else: ?>
                        <form method="post">
                            <input type="hidden" name="plan_type" value="<?php echo $plan['plan_type']; ?>">
                            <button type="submit" name="upgrade_plan" class="btn btn-primary btn-block upgrade-btn">
                                <i class="fas fa-arrow-circle-up mr-2"></i>Upgrade Now
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Plan Comparison Table -->
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h3 class="mb-0"><i class="fas fa-table mr-2"></i>Plan Comparison</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table plan-comparison-table mb-0">
                            <thead>
                                <tr>
                                    <th>Feature</th>
                                    <?php foreach ($plans as $plan): ?>
                                    <th class="text-center">
                                        <?php echo htmlspecialchars($plan['plan_name']); ?>
                                        <?php if ($plan['plan_type'] === $current_plan): ?>
                                        <div class="current-badge"><i class="fas fa-check-circle"></i></div>
                                        <?php endif; ?>
                                    </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Monthly Tokens</strong></td>
                                    <?php foreach ($plans as $plan): ?>
                                    <td class="text-center"><?php echo number_format($plan['monthly_tokens']); ?></td>
                                    <?php endforeach; ?>
                                </tr>
                                <tr>
                                    <td><strong>Data Analysis</strong></td>
                                    <td class="text-center">Basic</td>
                                    <td class="text-center">Advanced</td>
                                    <td class="text-center">Premium</td>
                                    <td class="text-center">Enterprise</td>
                                </tr>
                                <tr>
                                    <td><strong>Visualizations</strong></td>
                                    <td class="text-center">Standard</td>
                                    <td class="text-center">Extended</td>
                                    <td class="text-center">Custom</td>
                                    <td class="text-center">Advanced Custom</td>
                                </tr>
                                <tr>
                                    <td><strong>File Size Limit</strong></td>
                                    <td class="text-center">5 MB</td>
                                    <td class="text-center">20 MB</td>
                                    <td class="text-center">50 MB</td>
                                    <td class="text-center">100 MB</td>
                                </tr>
                                <tr>
                                    <td><strong>Support</strong></td>
                                    <td class="text-center">Email</td>
                                    <td class="text-center">Priority Email</td>
                                    <td class="text-center">Priority Support</td>
                                    <td class="text-center">Dedicated Support</td>
                                </tr>
                        
                                <tr>
                                    <td><strong>Team Collaboration</strong></td>
                                    <td class="text-center"><i class="fas fa-times text-danger"></i></td>
                                    <td class="text-center"><i class="fas fa-times text-danger"></i></td>
                                    <td class="text-center"><i class="fas fa-times text-danger"></i></td>
                                    <td class="text-center"><i class="fas fa-check text-success"></i></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- FAQ Section -->
    <div class="row mt-5">
        <div class="col-md-12">
            <h2 class="mb-4 text-center"><i class="fas fa-question-circle mr-2"></i>Frequently Asked Questions</h2>
            
            <div class="accordion" id="faqAccordion">
                <div class="card">
                    <div class="card-header" id="faqOne">
                        <h2 class="mb-0">
                            <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                <i class="fas fa-plus-circle mr-2"></i> How do I upgrade my plan?
                            </button>
                        </h2>
                    </div>
                    <div id="collapseOne" class="collapse show" aria-labelledby="faqOne" data-parent="#faqAccordion">
                        <div class="card-body">
                            Simply select the plan that best suits your needs and click the "Upgrade Now" button. You'll be guided through our secure payment process, and your account will be upgraded immediately after successful payment.
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header" id="faqTwo">
                        <h2 class="mb-0">
                            <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                <i class="fas fa-plus-circle mr-2"></i> Can I change my plan later?
                            </button>
                        </h2>
                    </div>
                    <div id="collapseTwo" class="collapse" aria-labelledby="faqTwo" data-parent="#faqAccordion">
                        <div class="card-body">
                            Yes, you can upgrade or downgrade your plan at any time. When upgrading, the new features will be available immediately. When downgrading, the changes will take effect at the start of your next billing cycle.
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header" id="faqThree">
                        <h2 class="mb-0">
                            <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                <i class="fas fa-plus-circle mr-2"></i> What happens if I run out of tokens?
                            </button>
                        </h2>
                    </div>
                    <div id="collapseThree" class="collapse" aria-labelledby="faqThree" data-parent="#faqAccordion">
                        <div class="card-body">
                            If you run out of tokens, you can purchase additional token packs without changing your plan. Alternatively, you can upgrade to a higher plan to receive more monthly tokens. Your tokens reset at the beginning of each billing cycle.
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header" id="faqFour">
                        <h2 class="mb-0">
                            <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                <i class="fas fa-plus-circle mr-2"></i> Do you offer refunds?
                            </button>
                        </h2>
                    </div>
                    <div id="collapseFour" class="collapse" aria-labelledby="faqFour" data-parent="#faqAccordion">
                        <div class="card-body">
                            We offer a 7-day money-back guarantee for all paid plans. If you're not satisfied with our service, contact our support team within 7 days of your purchase for a full refund.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Upgrade Plan Page Styles */
.upgrade-plan-container {
    padding-top: 2rem;
    padding-bottom: 3rem;
}

/* Plan Cards */
.plan-card {
    border-radius: 10px;
    overflow: hidden;
    transition: all 0.3s ease;
    position: relative;
    border: 1px solid #e3e6f0;
}

.plan-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.current-plan {
    border: 2px solid #4e73df;
}

.current-plan-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background-color: #4e73df;
    color: white;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    z-index: 10;
}

.popular-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    background-color: #f6c23e;
    color: white;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    z-index: 10;
}

.plan-name {
    font-size: 1.5rem;
    font-weight: 700;
}

.plan-price {
    position: relative;
}

.currency {
    position: relative;
    top: -15px;
    font-size: 1.2rem;
    font-weight: 700;
    color: #5a5c69;
}

.amount {
    font-size: 3rem;
    font-weight: 700;
    color: #3a3b45;
}

.period {
    font-size: 1rem;
    color: #858796;
}

.plan-tokens {
    padding: 15px 0;
}

.token-icon {
    font-size: 2rem;
    color: #f6c23e;
    margin-bottom: 5px;
}

.token-amount {
    font-size: 1.8rem;
    font-weight: 700;
    color: #3a3b45;
}

.token-label {
    font-size: 0.9rem;
    color: #858796;
}

.plan-features {
    list-style: none;
    padding-left: 0;
    margin-bottom: 1.5rem;
}

.plan-features li {
    padding: 8px 0;
    border-bottom: 1px solid #f8f9fc;
}

.plan-features li:last-child {
    border-bottom: none;
}

.plan-features li i {
    color: #1cc88a;
}

.upgrade-btn {
    border-radius: 30px;
    padding: 10px 20px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.upgrade-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

/* Plan Comparison Table */
.plan-comparison-table th, 
.plan-comparison-table td {
    padding: 15px;
    vertical-align: middle;
}

.plan-comparison-table thead th {
    background-color: #f8f9fc;
    position: relative;
}

.current-badge {
    position: absolute;
    top: 5px;
    right: 5px;
    color: #4e73df;
    font-size: 14px;
}

/* FAQ Section */
.accordion .card {
    border: 1px solid #e3e6f0;
    margin-bottom: 10px;
    border-radius: 5px;
    overflow: hidden;
}

.accordion .card-header {
    padding: 0;
    background-color: #f8f9fc;
}

.accordion .btn-link {
    color: #4e73df;
    font-weight: 600;
    text-decoration: none;
    padding: 15px;
}

.accordion .btn-link:hover {
    text-decoration: none;
    color: #2e59d9;
}

.accordion .btn-link.collapsed i.fas {
    transform: rotate(0deg);
}

.accordion .btn-link i.fas {
    transition: transform 0.3s ease;
    transform: rotate(45deg);
}

/* Responsive Adjustments */
@media (max-width: 992px) {
    .plan-card {
        margin-bottom: 30px;
    }
}

@media (max-width: 768px) {
    .plan-comparison-table {
        min-width: 650px;
    }
}
</style>

<script>
// Toggle FAQ icons
document.addEventListener('DOMContentLoaded', function() {
    const accordionButtons = document.querySelectorAll('.accordion .btn-link');
    
    accordionButtons.forEach(button => {
        button.addEventListener('click', function() {
            const icon = this.querySelector('i.fas');
            if (this.classList.contains('collapsed')) {
                icon.classList.remove('fa-minus-circle');
                icon.classList.add('fa-plus-circle');
            } else {
                icon.classList.remove('fa-plus-circle');
                icon.classList.add('fa-minus-circle');
            }
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>