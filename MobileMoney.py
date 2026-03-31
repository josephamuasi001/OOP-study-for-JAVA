

while:
    print("MTN MOBILE MONEY")
    print("1. Send Money")
    print("2. Receive Money")
    print("3. Check Balance")
    print("4. Exit")

    choice = input("Enter your choice: ")
    if choice == '1':
        amount = input("Enter the amount to send: ")
        recipient = input("Enter the recipient's phone number: ")
        print(f"Sending {amount} to {recipient}...")
        print("Transaction successful!")
    elif choice == '2':
        sender = input("Enter the sender's phone number: ")
        amount = input("Enter the amount received: ")
        print(f"Received {amount} from {sender}.")
    elif choice == '3':
        print("Your current balance is: 1000 UGX")
    elif choice == '4':
        print("Exiting...")
        break
    else:
        print("Invalid choice. Please try again.")