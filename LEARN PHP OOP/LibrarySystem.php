<?php
// Simple procedural functions for a website

function addToCart(&$cart, $productId, $productName, $price, $quantity) {
    $cart[] = [
        'id' => $productId,
        'name' => $productName,
        'price' => $price,
        'quantity' => $quantity,
        'total' => $price * $quantity
    ];
}

function getCartTotal($cart) {
    $total = 0;
    foreach ($cart as $item) {
        $total += $item['total'];
    }
    return $total;
}

function removeFromCart(&$cart, $productId) {

// Product Class
class Product {
    private $id;
    private $name;
    private $price;
    private $description;
    private $stock;

    public function __construct($id, $name, $price, $description, $stock) {
        $this->id = $id;
        $this->name = $name;
        $this->price = $price;
        $this->description = $description;
        $this->stock = $stock;
    }

    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
    public function getPrice() { return $this->price; }
    public function getDescription() { return $this->description; }
    public function getStock() { return $this->stock; }
}

// Cart Item Class
class CartItem {
    private $product;
    private $quantity;

    public function __construct(Product $product, $quantity) {
        $this->product = $product;
        $this->quantity = $quantity;
    }

    public function getProduct() { return $this->product; }
    public function getQuantity() { return $this->quantity; }
    public function getTotal() { return $this->product->getPrice() * $this->quantity; }
}

// Shopping Cart Class
class ShoppingCart {
    private $items = [];

    public function addItem(CartItem $item) {
        $this->items[] = $item;
    }

    public function getItems() { return $this->items; }
    public function getTotal() {
        $total = 0;
        foreach ($this->items as $item) {
            $total += $item->getTotal();
        }
        return $total;
    }
}

// Order Class
class Order {
    private $orderId;
    private $cart;
    private $status;

    public function __construct($orderId, ShoppingCart $cart) {
        $this->orderId = $orderId;
        $this->cart = $cart;
        $this->status = "pending";
    }

    public function getOrderId() { return $this->orderId; }
    public function getCart() { return $this->cart; }
    public function getStatus() { return $this->status; }
    public function setStatus($status) { $this->status = $status; }
}
?>
           
    


        
