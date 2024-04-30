# Task description

We need to make a simple testing system that supports fuzzy questions logic and the ability to choose multiple answers.

### What are fuzzy logic questions?
```
2 + 2 =
----------
1. 4
2. 3 + 1
3. 10
```
The correct answers here will be 1 OR 2 OR (1 AND 2). At the same time, any other combinations (for example, 1 AND 3) will not be considered correct, despite the fact that they ontain right answer.

## Criteria:
- The project must be wrapped in docker.
- The user should be able to take the test from start to finish and at the end see two lists - the questions he answered correctly and the questions where the answers contained errors.
- It should be possible to take the test as many times as you want.
- Each test result must be saved in the database (it is not necessary to output the results required).
- Both the questions and the answers for each question should be shown the user is randomly assigned to each new test series.
- There is no need to come up with questions, you can take the predefined list