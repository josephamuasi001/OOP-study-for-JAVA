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


