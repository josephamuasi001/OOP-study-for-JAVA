package OOPstudy;

public class Student {
    private String studentId;
    private String name;
    private int score;

    public Student(String studentId, String name, int score) {
        this.studentId = studentId;
        this.name = name;
        this.setScore(score);
    
    }
    public void setStudentId(String studentId) {
        this.studentId = studentId;
    }
    public void setName(String name) {
        this.name = name;
    }
    public void setScore(int score) {
        if (score >= 0 && score <= 100) {
            this.score = score;
        } else {
            System.out.println("Invalid score");
            System.out.println("Score must be between 0 and 100");

        }
    }


    public String getStudentId() {
        return studentId;
    }
    public String getName() {
        return name;
    }
    public int getScore() {
        return score;
    }
    public char getGrade() {
        if (score >= 70 && score <= 100) {
            return 'A';
        } else if (score >= 60) {
            return 'B';
        } else if (score >= 50) {
            return 'C';
        } else if (score >= 45) {
            return 'D';
        } else {
            return 'F';
        }
    }
}

