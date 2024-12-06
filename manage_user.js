document.addEventListener('DOMContentLoaded', function() {
 function toggleFields(role) {
    var professorFields = document.getElementById('professor-fields');
    var studentFields = document.getElementById('student-fields');

    if (role == 'professor') {
        professorFields.style.display = 'block';
        studentFields.style.display = 'none';

        // Remove required attribute from hidden student fields
        document.querySelectorAll('#student-fields input').forEach(function(input) {
            input.removeAttribute('required');
        });

        // Add required attribute to visible professor fields
        document.querySelectorAll('#professor-fields input').forEach(function(input) {
            input.setAttribute('required', 'required');
        });

    } else {
        studentFields.style.display = 'block';
        professorFields.style.display = 'none';

        // Remove required attribute from hidden professor fields
        document.querySelectorAll('#professor-fields input').forEach(function(input) {
            input.removeAttribute('required');
        });

        // Add required attribute to visible student fields
        document.querySelectorAll('#student-fields input').forEach(function(input) {
            input.setAttribute('required', 'required');
        });
    }
}

// Event listener to trigger toggleFields when the role is changed
document.querySelector('select[name="role"]').addEventListener('change', function() {
    toggleFields(this.value);
});
document.getElementById('update_field').addEventListener('change', function() {
var field = this.value;
var inputField = document.getElementById('update_input');

// Show the input field for entering new value
inputField.style.display = 'block';

// Change input placeholder based on selected field
if (field == 'name') {
    document.getElementById('new_value').placeholder = "Enter new name";
} else if (field == 'email') {
    document.getElementById('new_value').placeholder = "Enter new email";
} else if (field == 'contact_number') {
    document.getElementById('new_value').placeholder = "Enter new contact number";
} else if (field == 'department') {
    document.getElementById('new_value').placeholder = "Enter new department";
} else if (field == 'address') {
    document.getElementById('new_value').placeholder = "Enter new address";
}
});
document.getElementById('update_field_student').addEventListener('change', function() {
var field = this.value;
var inputField = document.getElementById('update_input_student');

// Show the input field for entering the new value
inputField.style.display = 'block';

// Change input placeholder based on selected field
if (field == 'name') {
    document.getElementById('new_value_student').placeholder = "Enter new name";
} else if (field == 'password') {
    document.getElementById('new_value_student').placeholder = "Enter new password";
} else if (field == 'course') {
    document.getElementById('new_value_student').placeholder = "Enter new course";
} else if (field == 'year') {
    document.getElementById('new_value_student').placeholder = "Enter new year";
} else if (field == 'section') {
    document.getElementById('new_value_student').placeholder = "Enter new section";
} else if (field == 'email') {
    document.getElementById('new_value_student').placeholder = "Enter new email";
} else if (field == 'contact_number') {
    document.getElementById('new_value_student').placeholder = "Enter new contact number";
} else if (field == 'attendance_record') {
    document.getElementById('new_value_student').placeholder = "Enter new attendance record";
} else if (field == 'total_attendance') {
    document.getElementById('new_value_student').placeholder = "Enter new total attendance";
}
});

});