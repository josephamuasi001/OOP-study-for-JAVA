# 1. Even/Odd number Investigation
while True:
    print("EVEN/ODD NUMBER SIMULATOR")
    print("1.Check number parity")
    print("2.Exit")

    choice = int(input("Select an option : "))

    if choice == 1:
        number = int(input("Enter a number: "))

        if number % 2 == 0 :
            print("Even number")
        else:
            print("Odd Number")
    elif choice == 2:
        print("Goodbyeeee!!!!")
        break

    else:
        print("Invalid option")



#2. Sum of numbers in a list

num = [1, 2, 3, 4, 5]
total = sum(num)
print(total)



#3. Simple Calculator

def add(a, b):
    return a + b

def sub(c, d):
    return c - d

def mul(e, f):
    return e * f

def div(g, h):
    return g / h

while True:
    print("Simple Calculator")
    print("What operation do you want to perform")
    print("1. Add")
    print("2. Subtract")
    print("3. Multiply")
    print("4. Divide")
    print("5. Exit")
    
    choice = int(input("Select an option: "))
    if choice == 1:
        a = int(input("Enter 1st number: "))
        b = int(input("Enter 2nd number: "))
        print("Sum: ", add(a, b))
    elif choice == 2:
        c =  int(input("Enter 1st number: "))
        d = int(input("Enter 2nd number: "))
        print("Difference: ", sub(c, d))
    elif choice == 3:
        e = int(input("Enter 1st number: "))
        f = int(input("Enter 2nd number: "))
        print("Product: ", mul(e, f))
    elif choice == 4:
        g = int(input("Enter 1st number: "))
        h = int(input("Enter 1st number: "))
        print("Quotient: ", div(g, h))
    elif choice == 5:
        print("Thank you for using my calculator")
        print("Goodbyyyyeeee!!!")
        break
    else:
        print("Invalid option")


#4. Loop through list to find maximum

li_num = [3, 4, 6, 8]

for i in li_num:
    max_num = max(li_num)

print("Maximum", max_num)

#5. Function that checks prime numbers

def chk_prime(num1):
    if num1 % 2 == 0:
        #got stuck here
    