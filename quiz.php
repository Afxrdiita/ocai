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

        .btn-row {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 50px;
        }

        .btn {
            padding: 14px 40px;
            border: 2px solid #00f0ff;
            border-radius: 10px;
            background: rgba(0, 240, 255, 0.05);
            color: #fff;
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 1px;
            text-decoration: none;
            box-shadow: 0 0 10px rgba(0, 240, 255, 0.5);
            transition: box-shadow 0.2s;
        }

        .btn:hover {
            box-shadow: 0 0 20px #00f0ff;
        }

        .btn.pink {
            border-color: #ff00e6;
            background: rgba(255, 0, 230, 0.1);
            box-shadow: 0 0 10px rgba(255, 0, 230, 0.5);
        }

        .btn.pink:hover {
            box-shadow: 0 0 20px #ff00e6;
        }

        .review-item {
            border: 1px solid #555;
            border-radius: 8px;
            padding: 14px 16px;
            margin: 10px 0;
            background: rgba(255, 255, 255, 0.03);
            text-align: left;
        }

        .review-question {
            color: #fff;
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 16px;
        }

        .review-answer {
            font-size: 15px;
            margin: 3px 0;
        }

        .review-answer.wrong {
            color: #ff5a5a;
        }

        .review-answer.correct {
            color: #5aff8a;
        }

        .review-perfect {
            color: #5aff8a;
            font-size: 17px;
            margin-top: 20px;
            font-weight: bold;
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

    <div class="btn-row">
        <a class="btn" href="facts.php">&larr; Back to Fun Facts</a>
    </div>

    <script>
        const questionPool = <?php
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
                ["q" => "How many strings does a standard guitar have?", "a" => ["Four", "Five", "Six", "Seven"], "c" => 2],
                ["q" => "What is the smallest country in the world?", "a" => ["Monaco", "Nauru", "San Marino", "Vatican City"], "c" => 3],
                ["q" => "Which gas do plants absorb from the atmosphere?", "a" => ["Oxygen", "Carbon dioxide", "Nitrogen", "Helium"], "c" => 1],
                ["q" => "Who painted the Mona Lisa?", "a" => ["Michelangelo", "Leonardo da Vinci", "Raphael", "Donatello"], "c" => 1],
                ["q" => "What is the tallest mountain in the world?", "a" => ["K2", "Kangchenjunga", "Mount Everest", "Lhotse"], "c" => 2],
                ["q" => "How many bones are in the adult human body?", "a" => ["186", "206", "226", "246"], "c" => 1],
                ["q" => "Which country invented tea?", "a" => ["India", "Japan", "China", "England"], "c" => 2],
                ["q" => "What does 'WWW' stand for?", "a" => ["World Wide Web", "World Web Wide", "Wide World Web", "Web World Wide"], "c" => 0],
                ["q" => "Which blood type is known as the universal donor?", "a" => ["A", "B", "AB", "O negative"], "c" => 3],
                ["q" => "How many players are on a soccer team on the field?", "a" => ["Nine", "Ten", "Eleven", "Twelve"], "c" => 2],
                ["q" => "What is the hardest natural substance on Earth?", "a" => ["Gold", "Iron", "Quartz", "Diamond"], "c" => 3],
                ["q" => "Which is the only mammal capable of true flight?", "a" => ["Flying squirrel", "Bat", "Sugar glider", "Colugo"], "c" => 1],
                ["q" => "What is the currency of Japan?", "a" => ["Won", "Yuan", "Yen", "Ringgit"], "c" => 2],
                ["q" => "Which ocean is the Bermuda Triangle in?", "a" => ["Pacific", "Indian", "Southern", "Atlantic"], "c" => 3],
                ["q" => "How many colors are in a rainbow?", "a" => ["Five", "Six", "Seven", "Eight"], "c" => 2],
                ["q" => "What is the fastest land animal?", "a" => ["Lion", "Cheetah", "Gazelle", "Pronghorn"], "c" => 1],
                ["q" => "Which vitamin is produced when skin is exposed to sunlight?", "a" => ["Vitamin A", "Vitamin B12", "Vitamin C", "Vitamin D"], "c" => 3],
                ["q" => "What is the largest desert in the world?", "a" => ["Sahara", "Gobi", "Antarctic Desert", "Arabian Desert"], "c" => 2],
                ["q" => "Which planet has the most moons?", "a" => ["Jupiter", "Saturn", "Uranus", "Neptune"], "c" => 1],
                ["q" => "How many minutes are in a full day?", "a" => ["1200", "1440", "1600", "2400"], "c" => 1],
                ["q" => "What is the capital of Canada?", "a" => ["Toronto", "Vancouver", "Ottawa", "Montreal"], "c" => 2],
                ["q" => "Who painted 'The Starry Night'?", "a" => ["Claude Monet", "Vincent van Gogh", "Pablo Picasso", "Salvador Dali"], "c" => 1],
                ["q" => "What is the largest planet in our solar system?", "a" => ["Saturn", "Earth", "Jupiter", "Neptune"], "c" => 2],
                ["q" => "What is the main gas in Earth's atmosphere?", "a" => ["Oxygen", "Nitrogen", "Carbon dioxide", "Argon"], "c" => 1],
                ["q" => "Which animal has the longest lifespan?", "a" => ["Elephant", "Greenland shark", "Galapagos tortoise", "Blue whale"], "c" => 1],
                ["q" => "What year did the Titanic sink?", "a" => ["1905", "1912", "1918", "1923"], "c" => 1],
                ["q" => "What is the smallest bone in the human body?", "a" => ["Stapes", "Femur", "Radius", "Tibia"], "c" => 0],
                ["q" => "Which country has the most islands?", "a" => ["Indonesia", "Sweden", "Japan", "Philippines"], "c" => 1],
                ["q" => "What is the national flower of Japan?", "a" => ["Lotus", "Cherry blossom", "Rose", "Orchid"], "c" => 1],
                ["q" => "How many hearts does an earthworm have?", "a" => ["One", "Three", "Five", "Eight"], "c" => 2],
                ["q" => "What does CPU stand for?", "a" => ["Central Processing Unit", "Computer Power Unit", "Central Program Utility", "Core Processing Underlay"], "c" => 0],
                ["q" => "Which sea creature has three hearts?", "a" => ["Dolphin", "Octopus", "Shark", "Jellyfish"], "c" => 1],
                ["q" => "What is the tallest waterfall in the world?", "a" => ["Niagara Falls", "Victoria Falls", "Angel Falls", "Iguazu Falls"], "c" => 2],
                ["q" => "Who was the first person in space?", "a" => ["Neil Armstrong", "Buzz Aldrin", "Yuri Gagarin", "John Glenn"], "c" => 2],
                ["q" => "What is the capital of Brazil?", "a" => ["Rio de Janeiro", "Sao Paulo", "Brasilia", "Salvador"], "c" => 2],
                ["q" => "Which metal is liquid at room temperature?", "a" => ["Mercury", "Aluminium", "Zinc", "Tin"], "c" => 0],
                ["q" => "How many strings does a violin have?", "a" => ["Four", "Five", "Six", "Seven"], "c" => 0],
                ["q" => "What is the world's largest coral reef system?", "a" => ["Red Sea Coral Reef", "Great Barrier Reef", "Belize Barrier Reef", "Maldives Reef"], "c" => 1],
                ["q" => "Which language has the most native speakers?", "a" => ["English", "Spanish", "Mandarin Chinese", "Hindi"], "c" => 2],
                ["q" => "What does HTML stand for?", "a" => ["HyperText Markup Language", "HighText Machine Language", "HyperTool Multi Language", "HomeText Markup Language"], "c" => 0],
                ["q" => "Which fruit has its seeds on the outside?", "a" => ["Blueberry", "Strawberry", "Raspberry", "Grape"], "c" => 1],
                ["q" => "What is the capital of Egypt?", "a" => ["Alexandria", "Luxor", "Cairo", "Giza"], "c" => 2],
                ["q" => "Which planet is closest to the Sun?", "a" => ["Venus", "Mercury", "Mars", "Earth"], "c" => 1],
                ["q" => "What is the chemical symbol for silver?", "a" => ["Si", "Sv", "Ag", "Au"], "c" => 2],
                ["q" => "How many legs does a spider have?", "a" => ["Six", "Eight", "Ten", "Twelve"], "c" => 1],
                ["q" => "Which country gifted the Statue of Liberty to the USA?", "a" => ["England", "Spain", "France", "Italy"], "c" => 2],
                ["q" => "What is the largest mammal on Earth?", "a" => ["African elephant", "Giraffe", "Blue whale", "Orca"], "c" => 2],
                ["q" => "Which sport is played at Wimbledon?", "a" => ["Golf", "Tennis", "Cricket", "Rugby"], "c" => 1],
                ["q" => "What is the freezing point of water in Fahrenheit?", "a" => ["0", "32", "50", "100"], "c" => 1],
                ["q" => "Who developed the theory of relativity?", "a" => ["Isaac Newton", "Albert Einstein", "Galileo Galilei", "Stephen Hawking"], "c" => 1],
                ["q" => "What is the capital of South Korea?", "a" => ["Busan", "Incheon", "Seoul", "Daegu"], "c" => 2],
                ["q" => "Which animal is the fastest in the ocean?", "a" => ["Dolphin", "Sailfish", "Blue marlin", "Orca"], "c" => 1],
                ["q" => "How many time zones does Russia span?", "a" => ["8", "9", "11", "13"], "c" => 2],
                ["q" => "What is the main ingredient in guacamole?", "a" => ["Peas", "Avocado", "Lima beans", "Zucchini"], "c" => 1],
                ["q" => "Which planet has a ring system?", "a" => ["Mars", "Venus", "Saturn", "Mercury"], "c" => 2],
                ["q" => "What is the capital of Spain?", "a" => ["Barcelona", "Madrid", "Seville", "Valencia"], "c" => 1],
                ["q" => "Which US state is the largest by area?", "a" => ["Texas", "California", "Alaska", "Montana"], "c" => 2],
                ["q" => "What is the hottest planet in our solar system?", "a" => ["Mercury", "Venus", "Mars", "Jupiter"], "c" => 1],
                ["q" => "What is the smallest country by population?", "a" => ["Monaco", "Nauru", "Vatican City", "Tuvalu"], "c" => 2],
                ["q" => "Who wrote 'The Adventures of Huckleberry Finn'?", "a" => ["Mark Twain", "Charles Dickens", "Ernest Hemingway", "Jules Verne"], "c" => 0],
                ["q" => "What is the capital of Italy?", "a" => ["Milan", "Naples", "Rome", "Florence"], "c" => 2],
                ["q" => "Which element makes up most of the universe?", "a" => ["Helium", "Hydrogen", "Oxygen", "Carbon"], "c" => 1],
                ["q" => "What is a group of lions called?", "a" => ["Pack", "Herd", "Pride", "Flock"], "c" => 2],
                ["q" => "Which instrument has 88 keys?", "a" => ["Organ", "Piano", "Accordion", "Harpsichord"], "c" => 1],
                ["q" => "What is the capital of the Netherlands?", "a" => ["Rotterdam", "The Hague", "Amsterdam", "Utrecht"], "c" => 2],
                ["q" => "Which sea is the saltiest?", "a" => ["Red Sea", "Dead Sea", "Black Sea", "Baltic Sea"], "c" => 1],
                ["q" => "How many Grammy Awards did Michael Jackson win in one night?", "a" => ["6", "7", "8", "9"], "c" => 2],
                ["q" => "What is the longest river in Asia?", "a" => ["Mekong", "Ganges", "Yangtze", "Yellow River"], "c" => 2],
                ["q" => "Which company created the first mass-produced car?", "a" => ["General Motors", "Ford", "Chrysler", "Toyota"], "c" => 1],
                ["q" => "What is the capital of Switzerland?", "a" => ["Zurich", "Geneva", "Bern", "Basel"], "c" => 2],
                ["q" => "Which bird can fly backwards?", "a" => ["Sparrow", "Hummingbird", "Swallow", "Eagle"], "c" => 1],
                ["q" => "What is the chemical symbol for iron?", "a" => ["Ir", "In", "Fe", "I"], "c" => 2],
                ["q" => "How many days does it take the Moon to orbit Earth?", "a" => ["About 14", "About 27", "About 31", "About 45"], "c" => 1],
                ["q" => "What is the capital of Argentina?", "a" => ["Rosario", "Mendoza", "Buenos Aires", "Cordoba"], "c" => 2],
                ["q" => "Which vitamin helps blood clotting?", "a" => ["Vitamin A", "Vitamin B", "Vitamin C", "Vitamin K"], "c" => 3],
                ["q" => "What is the world's largest island?", "a" => ["Borneo", "Madagascar", "Greenland", "New Guinea"], "c" => 2],
                ["q" => "Who invented the World Wide Web?", "a" => ["Bill Gates", "Tim Berners-Lee", "Steve Jobs", "Alan Turing"], "c" => 1],
                ["q" => "What is the capital of Ireland?", "a" => ["Cork", "Galway", "Dublin", "Limerick"], "c" => 2],
                ["q" => "Which is the densest planet in our solar system?", "a" => ["Earth", "Mercury", "Mars", "Venus"], "c" => 1],
                ["q" => "What is the most abundant element in the human body?", "a" => ["Carbon", "Oxygen", "Hydrogen", "Nitrogen"], "c" => 1],
                ["q" => "Which continent is the driest?", "a" => ["Africa", "Asia", "Antarctica", "Australia"], "c" => 2],
                ["q" => "What is the capital of India?", "a" => ["Mumbai", "New Delhi", "Kolkata", "Bangalore"], "c" => 1],
                ["q" => "Which language is the most spoken worldwide (including non-native)?", "a" => ["Mandarin Chinese", "English", "Spanish", "Arabic"], "c" => 1],
                ["q" => "What does RAM stand for in computing?", "a" => ["Random Access Memory", "Read Access Memory", "Rapid Application Module", "Runtime Active Memory"], "c" => 0],
                ["q" => "Which animal never sleeps?", "a" => ["Bullfrog", "Giraffe", "Sloth", "Koala"], "c" => 0],
                ["q" => "What is the deepest point in the ocean called?", "a" => ["Mariana Trench", "Java Trench", "Puerto Rico Trench", "Tonga Trench"], "c" => 0],
                ["q" => "What is the capital of Portugal?", "a" => ["Porto", "Lisbon", "Faro", "Braga"], "c" => 1],
                ["q" => "Which gas makes up most of the Sun?", "a" => ["Helium", "Hydrogen", "Oxygen", "Neon"], "c" => 1],
                ["q" => "What is the capital of Norway?", "a" => ["Bergen", "Trondheim", "Oslo", "Stavanger"], "c" => 2],
                ["q" => "How many players are on a rugby union team?", "a" => ["Eleven", "Thirteen", "Fifteen", "Seventeen"], "c" => 2],
            ];
            echo json_encode($questions);
        ?>;

        const QUESTIONS_PER_TEST = 10;
        const QUESTION_HISTORY_KEY = "quizQuestionHistory";
        const HISTORY_LIMIT = 50;

        const quizBox = document.getElementById("quiz-box");
        const progressEl = document.getElementById("progress");
        const questionEl = document.getElementById("question");
        const answersEl = document.getElementById("answers");

        let questions = [];
        let current = 0;
        let score = 0;
        let locked = false;
        let wrongAnswers = [];

        function shuffle(array) {
            for (let i = array.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [array[i], array[j]] = [array[j], array[i]];
            }
            return array;
        }

        function startTest() {
            current = 0;
            score = 0;
            wrongAnswers = [];

            let history = [];
            try {
                const stored = JSON.parse(localStorage.getItem(QUESTION_HISTORY_KEY));
                history = Array.isArray(stored) ? stored : [];
            } catch (e) {
            }

            let candidates = questionPool.map((_, i) => i).filter(i => !history.includes(i));
            while (candidates.length < QUESTIONS_PER_TEST) {
                history.shift();
                candidates = questionPool.map((_, i) => i).filter(i => !history.includes(i));
            }

            const chosen = shuffle(candidates).slice(0, QUESTIONS_PER_TEST);
            questions = chosen.map(i => questionPool[i]);

            history.push(...chosen);
            if (history.length > HISTORY_LIMIT) {
                history = history.slice(-HISTORY_LIMIT);
            }
            try {
                localStorage.setItem(QUESTION_HISTORY_KEY, JSON.stringify(history));
            } catch (e) {
            }

            questions.forEach(q => {
                const correct = q.a[q.c];
                q.a = shuffle([...q.a]);
                q.c = q.a.indexOf(correct);
            });
            showQuestion();
        }

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
            const q = questions[current];
            if (i === q.c) {
                score++;
            } else {
                wrongAnswers.push({
                    question: q.q,
                    yourAnswer: q.a[i],
                    correctAnswer: q.a[q.c]
                });
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

            let reviewHtml = "";
            if (wrongAnswers.length > 0) {
                reviewHtml = '<div class="progress" style="margin-top:25px;">Questions you got wrong</div>';
                wrongAnswers.forEach((w) => {
                    reviewHtml += '<div class="review-item">' +
                        '<div class="review-question">' + w.question + '</div>' +
                        '<div class="review-answer wrong">Your answer: ' + w.yourAnswer + '</div>' +
                        '<div class="review-answer correct">Correct answer: ' + w.correctAnswer + '</div>' +
                        '</div>';
                });
            } else {
                reviewHtml = '<div class="review-perfect">Perfect score — nothing to review!</div>';
            }

            quizBox.innerHTML =
                '<div class="progress">Test Complete</div>' +
                '<div class="score">You scored ' + score + ' / ' + questions.length +
                ' (' + percent + '%)</div>' +
                reviewHtml +
                '<div style="text-align:center;margin-top:25px;"><a class="btn pink" href="quiz.php">Take the test again</a></div>';
        }

        startTest();
    </script>
</body>
</html>
