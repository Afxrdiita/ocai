<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>General Knowledge Test</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #0a0a12;
            font-family: Arial, sans-serif;
        }

        .title-box {
            border: 4px solid #00f0ff;
            border-radius: 18px;
            padding: 20px 60px;
            margin-bottom: 40px;
            background: rgba(0, 240, 255, 0.05);
            box-shadow:
                0 0 10px #00f0ff,
                0 0 30px #00f0ff,
                0 0 60px rgba(0, 240, 255, 0.5),
                inset 0 0 15px rgba(0, 240, 255, 0.3);
        }

        .title-box h1 {
            margin: 0;
            font-size: 40px;
            font-weight: 900;
            letter-spacing: 4px;
            color: #fff;
            text-shadow:
                0 0 10px #00f0ff,
                0 0 30px #00f0ff,
                0 0 60px #00f0ff;
            text-transform: uppercase;
        }

        .quiz-box {
            border: 2px solid #ff00e6;
            border-radius: 12px;
            padding: 30px;
            width: 520px;
            background: rgba(255, 0, 230, 0.05);
            box-shadow:
                0 0 10px #ff00e6,
                0 0 30px rgba(255, 0, 230, 0.6);
        }

        .progress {
            color: #00f0ff;
            font-size: 14px;
            letter-spacing: 2px;
            margin-bottom: 15px;
            text-transform: uppercase;
        }

        .question {
            color: #fff;
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 25px;
            line-height: 1.4;
        }

        .answer {
            display: block;
            width: 100%;
            box-sizing: border-box;
            margin: 10px 0;
            padding: 14px 20px;
            border: 2px solid #555;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.05);
            color: #eee;
            font-size: 17px;
            text-align: left;
            cursor: pointer;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .answer:hover {
            border-color: #00f0ff;
            box-shadow: 0 0 10px rgba(0, 240, 255, 0.5);
        }

        .score {
            color: #fff;
            font-size: 32px;
            font-weight: 900;
            margin-bottom: 25px;
            text-shadow: 0 0 10px #00f0ff;
        }

        .result-fact {
            border: 2px solid #00f0ff;
            border-radius: 10px;
            padding: 20px;
            color: #eee;
            font-size: 18px;
            line-height: 1.5;
            background: rgba(0, 240, 255, 0.05);
            box-shadow: 0 0 15px rgba(0, 240, 255, 0.5);
            margin-bottom: 25px;
        }

        .restart {
            padding: 12px 30px;
            border: 2px solid #ff00e6;
            border-radius: 8px;
            background: rgba(255, 0, 230, 0.1);
            color: #fff;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            letter-spacing: 1px;
            transition: box-shadow 0.2s;
        }

        .restart:hover {
            box-shadow: 0 0 12px #ff00e6;
        }
    </style>
</head>
<body>
    <div class="title-box">
        <h1>The Test</h1>
    </div>

    <div class="quiz-box" id="quiz-box">
        <div class="progress" id="progress"></div>
        <div class="question" id="question"></div>
        <div id="answers"></div>
    </div>

    <script>
        const questions = <?php
            $questions = [
                ["q" => "What is the capital of Australia?", "a" => ["Sydney", "Canberra", "Melbourne", "Perth"], "c" => 1],
                ["q" => "Which planet is known as the Red Planet?", "a" => ["Venus", "Jupiter", "Mars", "Mercury"], "c" => 2],
                ["q" => "How many hearts does an octopus have?", "a" => ["One", "Two", "Three", "Eight"], "c" => 2],
                ["q" => "Who wrote the play 'Romeo and Juliet'?", "a" => ["Charles Dickens", "William Shakespeare", "Jane Austen", "Mark Twain"], "c" => 1],
                ["q" => "What is the chemical symbol for gold?", "a" => ["Go", "Gd", "Ag", "Au"], "c" => 3],
                ["q" => "What is the largest ocean on Earth?", "a" => ["Atlantic", "Indian", "Arctic", "Pacific"], "c" => 3],
                ["q" => "In which year did the first man walk on the Moon?", "a" => ["1965", "1969", "1972", "1958"], "c" => 1],
                ["q" => "How many continents are there?", "a" => ["Five", "Six", "Seven", "Eight"], "c" => 2],
                ["q" => "What is the longest river in the world?", "a" => ["Amazon", "Nile", "Yangtze", "Mississippi"], "c" => 1],
                ["q" => "Which animal is known as the King of the Jungle?", "a" => ["Tiger", "Elephant", "Lion", "Gorilla"], "c" => 2],
            ];
            echo json_encode($questions);
        ?>;

        const resultFacts = {
            perfect: "You're rarer than a perfect game of bowling — a 300 game happens only once in about 11,500 games!",
            great: "Honey never spoils — edible pots found in ancient tombs are over 3,000 years old and still good. Great minds think sweet!",
            good: "A group of flamingos is called a flamboyance. Like you today — pretty in pink!",
            poor: "Hot water can freeze faster than cold water — the Mpemba effect. Some things in science are counterintuitive, just like some quiz answers!",
            bad: "The first computer bug was an actual moth, found in 1947. Everyone starts somewhere — even debugging started with a bug!"
        };

        const quizBox = document.getElementById("quiz-box");
        const progressEl = document.getElementById("progress");
        const questionEl = document.getElementById("question");
        const answersEl = document.getElementById("answers");

        let current = 0;
        let score = 0;
        let locked = false;

        function showQuestion() {
            locked = false;
            const q = questions[current];
            progressEl.textContent = "Question " + (current + 1) + " of " + questions.length;
            questionEl.textContent = q.q;
            answersEl.innerHTML = "";
            q.a.forEach((answer, i) => {
                const btn = document.createElement("button");
                btn.className = "answer";
                btn.textContent = answer;
                btn.addEventListener("click", () => selectAnswer(i));
                answersEl.appendChild(btn);
            });
        }

        function selectAnswer(i) {
            if (locked) return;
            locked = true;
            if (i === questions[current].c) {
                score++;
            }
            current++;
            setTimeout(() => {
                if (current < questions.length) {
                    showQuestion();
                } else {
                    showResult();
                }
            }, 250);
        }

        function showResult() {
            const percent = Math.round((score / questions.length) * 100);
            let fact;
            if (percent === 100) fact = resultFacts.perfect;
            else if (percent >= 80) fact = resultFacts.great;
            else if (percent >= 50) fact = resultFacts.good;
            else if (percent >= 30) fact = resultFacts.poor;
            else fact = resultFacts.bad;

            quizBox.innerHTML =
                '<div class="progress">Test Complete</div>' +
                '<div class="score">You scored ' + score + ' / ' + questions.length +
                ' (' + percent + '%)</div>' +
                '<div class="result-fact">' + fact + '</div>' +
                '<a class="restart" href="facts.php">Take the test again</a>';
        }

        showQuestion();
    </script>
</body>
</html>
