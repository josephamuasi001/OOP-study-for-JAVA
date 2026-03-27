package OOPEXERCISES;

public class Teacher extends Person{
    String subject;

    Teacher(String name, int age, String subject) {
        super(name, age);
        this.subject = subject;
    }
}
