# 1. EVEN / ODD NUMBER SIMULATOR
while True:
    print("\nEVEN/ODD NUMBER SIMULATOR")
    print("1. Check number parity")
    print("2. Exit")

    try:
        choice = int(input("Select an option: "))

        if choice == 1:
            number = int(input("Enter a number: "))

            if number % 2 == 0:
                print("Even number")
            else:
                print("Odd number")

        elif choice == 2:
            print("Goodbye!")
            break

        else:
            print("Invalid option")

    except ValueError:
        print("Please enter a valid number")


# 2. SUM OF NUMBERS IN A LIST (MANUAL METHOD)
num = [1, 2, 3, 4, 5]
total = 0

for n in num:
    total += n

print("Sum:", total)


# 3. SIMPLE CALCULATOR

def add(a, b):
    return a + b

def sub(a, b):
    return a - b

def mul(a, b):
    return a * b

def div(a, b):
    if b == 0:
        return "Cannot divide by zero"
    return a / b


while True:
    print("\nSIMPLE CALCULATOR")
    print("1. Add")
    print("2. Subtract")
    print("3. Multiply")
    print("4. Divide")
    print("5. Exit")

    try:
        choice = int(input("Select an option: "))

        if choice in [1, 2, 3, 4]:
            a = float(input("Enter 1st number: "))
            b = float(input("Enter 2nd number: "))

            if choice == 1:
                print("Sum:", add(a, b))
            elif choice == 2:
                print("Difference:", sub(a, b))
            elif choice == 3:
                print("Product:", mul(a, b))
            elif choice == 4:
                print("Quotient:", div(a, b))

        elif choice == 5:
            print("Thank you for using the calculator. Goodbye!")
            break

        else:
            print("Invalid option")

    except ValueError:
        print("Please enter valid numbers")


# 4. FIND MAXIMUM IN A LIST (MANUAL METHOD)
li_num = [3, 4, 6, 8]
max_num = li_num[0]

for num in li_num:
    if num > max_num:
        max_num = num

print("Maximum:", max_num)


# 5. PRIME NUMBER CHECKER

def chk_prime(num):
    if num <= 1:
        return "Not Prime"

    for i in range(2, int(num ** 0.5) + 1):
        if num % i == 0:
            return "Not Prime"

    return "Prime"


# Example usage
number = int(input("Enter a number to check if it's prime: "))
print(chk_prime(number))