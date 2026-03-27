package OOPstudy;

public class Course {
    private String courseCode;
    private String courseName;
    private int marks;

    public Course(String courseCode, String courseName, int marks) {
        this.courseCode = courseCode;
        this.courseName = courseName;
        this.setMark(marks);
    }

    public void setCourseCode(String courseCode) {
        this.courseCode = courseCode;
    }
    public void setCourseName(String courseName) {
        this.courseName = courseName;
    }
    public void setMark(int marks) {
        if (marks >= 0 && marks <= 100) {
            this.marks = marks;
        } else {
            System.out.println("This is an invalid mark !!! Marks must be in the range(0 - 100)");
        }
        
    }

    public String getCourseCode() {
        return this.courseCode;
    }

    public String getCourseName() {
        return this.courseName;
    }

    public int getMarks() {
        return this.marks;
    }

    public void getRemark() {
        if (marks >= 80 && marks <= 100) {
            System.out.println("Excellent");
        } else if ( marks >= 60) {
            System.out.println("Good");
        } else if (marks >= 50) {
            System.out.println("Average");
        } else {
            System.out.println("Fail");
        }
    }
}

