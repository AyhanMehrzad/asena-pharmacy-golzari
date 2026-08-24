# ASENA - Project Settings & Guidelines

This document serves as the absolute Source of Truth for the ASENA (ASENA) eCommerce platform. Whenever prompted to "make this page in our project settings" or "follow project guidelines", the rules within this document MUST be strictly followed.

## 1. Global Color Palette & Design System

The project uses a custom Material Design 3 inspired Tailwind CSS configuration located at `assets/js/tailwind-config.js`. 

### Key Brand Colors:
*   **Primary (`text-primary`, `bg-primary`)**: `#001a48` (Dark Navy Blue) - Used for primary headings, active states, and dominant branding.
*   **Primary Container (`bg-primary-container`)**: `#002d72` - Used for heavy call-to-action sections and hero backgrounds.
*   **Secondary (`bg-secondary`, `text-secondary`)**: `#954a00` - Used for secondary actions.
*   **Secondary Container (`bg-secondary-container`)**: `#fd8100` (Bright Orange) - Used heavily for "Add to Cart" buttons, sale tags, and highlights.
*   **Error (`bg-error`, `text-error`)**: `#ba1a1a` - Used for validation errors, sale prices, and delete actions.

### Typography & Layout:
*   **Font**: The project utilizes the `Geist` font family.
*   **Direction**: The entire application is Right-To-Left (RTL). All HTML tags must be `<html dir="rtl" lang="fa">`.
*   **Spacing**: 
    *   Maximum Container Width: `max-w-container-max` (1280px).
    *   Standard Desktop Padding: `px-margin-desktop` (24px).

## 2. Standard Page Structure

Every new frontend page MUST follow this exact skeletal structure to ensure the header, footer, and styling are perfectly consistent:

```php
<?php
require_once 'includes/db.php'; // ONLY if database access is needed

// [PHP Logic Here]

require_once 'includes/header.php'; // ALWAYS required
?>

<main class="max-w-container-max mx-auto overflow-hidden py-8 px-margin-desktop min-h-[70vh]">
    <!-- Page Content Goes Here -->
</main>

<?php include 'includes/footer.php'; // ALWAYS required ?>
```

## 3. Functional Requirements

### Database & Security
*   **Database Connection**: Always include `includes/db.php`.
*   **Queries**: STRICTLY use PDO Prepared Statements for all database interactions to prevent SQL injection. NEVER concatenate variables into SQL strings.
*   **Passwords**: Passwords must be hashed using `password_hash()` and verified using `password_verify()`.

### State Management & Forms
*   **Form Submissions**: Forms should ideally use `POST` for sensitive data (login, cart actions) and `GET` for filtering/search (shop page).
*   **Session Management**: User authentication is tracked via `$_SESSION['user_id']`. Pages requiring login must verify this session variable and redirect to `loginpage.php` if absent.

## 4. Component Library

### The ASENA Animated Product Card
This is the standard, user-approved design for product cards. It features rounded corners, a scaling image on hover, and an animated "Add to Cart" form that slides up from the bottom.

```html
<div class="bg-white rounded-3xl p-6 shadow-lg hover:shadow-2xl transition-all duration-300 group flex flex-col relative border border-outline-variant/10">
    <!-- Optional Badge -->
    <div class="absolute top-6 left-6 bg-secondary-container text-on-secondary-container text-label-sm px-3 py-1 rounded-full z-10 font-bold">تخفیف ویژه</div>

    <div class="aspect-square bg-surface-container-lowest rounded-2xl mb-6 overflow-hidden relative">
        <img src="path/to/image.jpg" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" alt="Product Name">
        
        <!-- Animated Add to Cart Overlay -->
        <form action="cart_action.php" method="POST" class="absolute inset-x-0 bottom-0 p-4 translate-y-full group-hover:translate-y-0 transition-transform duration-300 bg-gradient-to-t from-black/60 to-transparent flex justify-center z-20">
            <input type="hidden" name="product_id" value="[PRODUCT_ID]">
            <input type="hidden" name="action" value="add">
            <button type="submit" class="bg-primary text-white w-full py-3 rounded-xl font-bold flex justify-center items-center gap-2 hover:bg-primary-container">
                <span class="material-symbols-outlined">add_shopping_cart</span>
                افزودن به سبد
            </button>
        </form>
    </div>
    
    <div class="flex-1 flex flex-col">
        <p class="text-label-sm text-on-surface-variant mb-1">Category Name</p>
        <h3 class="text-title-lg font-bold text-on-surface mb-4 line-clamp-2">Product Name</h3>
        
        <div class="mt-auto flex justify-between items-center">
            <div class="flex flex-col">
                <!-- Discount Pricing State -->
                <span class="text-label-sm text-on-surface-variant line-through mb-1">200,000 تومان</span>
                <span class="text-title-lg font-bold text-primary">180,000 تومان</span>
            </div>
        </div>
    </div>
</div>
```

### Action Buttons
*   **Primary Action**: `<button class="bg-primary text-white py-3 px-6 rounded-lg font-bold hover:bg-primary-container transition-colors">Submit</button>`
*   **Secondary/Highlight Action (e.g., Cart)**: `<button class="bg-[#f97316] text-white py-2 px-4 font-bold rounded hover:bg-[#ea580c] transition-colors">Action</button>`

## 5. Autoship & Pet Pharmacy Architecture (Chewy Model)

### Chewy-Style Autoship & Subscription Architecture
*   **Item-Level Autoship ("Subscribe & Save")**: Products with `is_autoship = 1` must support both one-time purchasing and recurring delivery with an automatic `autoship_discount` (typically 10%–15%).
*   **Unified Cart & Checkout**: Regular items, pharmacy products, and Autoship items share the standard cart workflow (`cart.php`). Upon order approval, recurring delivery schedules are provisioned in `autoship_subscriptions`.
*   **Tiered Curated Plans**: High-tier concierge plans in `subscriptions.php` (3-Month, 6-Month, 12-Month packages with free veterinary clinic vouchers and express delivery) coexist with item-level autoship.

### Pet Pharmacy Sub-Store Standards
*   **Domain Differentiation**: Veterinary pharmaceuticals and prescription medications are distinctly served on `pharmacy.php` and flagged with category badges (`💊 داروخانه تخصصی` vs `🛍️ پت‌شاپ`).
*   **Multi-Species Targeting**: All pharmacy products must define `target_animal` (`dog`, `cat`, `horse`, `cow`, `chick`, `all`).
*   **Clinical Taxonomy**: Veterinary items are classified into 9 standard tags (`drugs`, `pain_management`, `inflammation`, `vitamins`, `therapy`, `dewormer`, `hoof_care`, `first_aid`, `vaccines`).
*   **Unified Customer Orders Surface**: In user profiles (`profile.php`), all order items appear in the unified order stream with functional clickable thumbnails, species tags, and pharmacy indicators.

## 6. Veterinary Pharmacy UX, Multi-Surface Synchronization & SEO Standard

### 6.1 Pharmacy UX & Trust Architecture
1. **Prescription Management (Rx Workflow)**:
   - Products flagged with prescription requirements must feature an intuitive 3-step upload modal (Photo of Rx, Clinic/Vet contact info, Patient/Pet name).
   - Display a prominent verification hotline banner (پشتیبانی ۲۴/۷ و مشاوره داروساز).
2. **Predictive Search & Medication Facets**:
   - Live search input on `pharmacy.php` supports generic drug names, active compounds, and brand laboratories (*Bayer, GimCat, Feliway, Razi, Ceva*).
   - Multi-faceted filter sidebar with 9 clinical tags, target species, customer star ratings, and price bands.
3. **Autoship ("Subscribe & Save") Convenience**:
   - Prominent Autoship frequency selector (هر ۲ هفته، ماهانه، هر ۲ ماه) on product cards and checkout with guaranteed 10%–15% recurring discounts.

### 6.2 End-to-End Multi-Surface Synchronization
All pharmacy and pet shop transactions must remain strictly synchronized across 3 surfaces:
*   **Admin Panel (`admin/inventory.php` & `admin/orders.php`)**: Single source of truth for stock, target species, medical tags, and prescription statuses.
*   **User Profile (`profile.php`)**: Interactive stream showing clickable thumbnails, domain distinction (`[💊 داروخانه]` vs `[🛍️ پت‌شاپ]`), and one-click repeat orders.
*   **Catalog Segregation**: `shop.php` strictly for Pet Supplies; `pharmacy.php` strictly for Veterinary Drugs & Supplements.

### 6.3 Veterinary Pharmacy SEO & Structured Data (JSON-LD)
1. **Schema.org Structured Data**:
   - `VeterinaryCare` & `Pharmacy` Schema defining business authority.
   - `Product` & `AggregateRating` Schema enabling Google Rich Snippets with pricing, stock availability, and star ratings.
   - `FAQPage` Schema answering top medication search queries to capture zero-click answers.
   - `BreadcrumbList` Schema defining hierarchical paths.
2. **Dynamic On-Page SEO**:
   - Programmatic `<title>` and `<meta name="description">` based on active species and clinical tags.

## 7. Multi-Surface Rating & Review Standards (Bayesian Model)

### 7.1 Mathematical Bayesian Weighted Averaging
*   All product and doctor ratings use the prior-damped Bayesian formula:
    $$\text{Score} = \frac{(5 \times \text{baseline\_rating}) + \sum \text{user\_ratings}}{5 + n}$$
*   **Cold-Start Transparency**: When review count $n = 0$, the UI explicitly identifies the score as an expert baseline (`امتیاز کارشناسی`) rather than fabricated user reviews. As real reviews accumulate, the real customer score seamlessly takes precedence.

### 7.2 Non-Intrusive UX & Loyalty Incentives
*   **"Task, Then Ask"**: Review prompts must only appear post-fulfillment in `profile.php` or post-consultation in `booking.php`.
*   **Loyalty Points Integration**: Each verified user review automatically credits `+5` loyalty points to the user's wallet via `actions/rewards_action.php`.

### 7.3 Admin Panel Controls
*   Admin Add/Edit product and doctor forms include a `baseline_rating` input field (Default: `4.5` - `5.0`).
*   Admin reviews management dashboard allows moderating comments and verifying buyer statuses.



