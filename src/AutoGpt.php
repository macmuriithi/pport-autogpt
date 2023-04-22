<?php

namespace Pport\Gpt;

class AutoGpt
{
    protected $apiKey;
    protected $objective;
    protected $taskList;
    protected $context;
    protected $initialTask;
    protected $taskCounter = 1;
    protected $completedTasks = [];

    public function __construct($objective = NULL, $initialTask = NULL, $apiKey = NULL)
    {
        $this->setObjective($objective);
        $this->setInitialTask($initialTask);
        $this->setApiKey($apiKey);
        $this->context = [];
    }

    protected function generateObjectiveTasks()
    {
        if ($this->initialTask) {
            return  $this->storeTask($this->initialTask);
        } else {
            $prompt = "Objective: $this->objective\nTask: Generate the first initial task that would meet the objective.\nYour Response:";
            return $this->sendRequest($prompt);
        }
    }

    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function setObjective($objective)
    {
        $this->objective = $objective;
    }

    public function setInitialTask($initialTask)
    {
        $this->initialTask = $initialTask;
    }

    protected function appendNewTasks($newTasks)
    {
        foreach ($newTasks as $task) {
            $this->taskList[] = $task;
        }
        return $this->taskList;
    }

    protected function saveCompletedTask($task, $result)
    {
        //Save to ResultStorage Engine
        $this->completedTasks[$this->taskCounter] = ['task' => $task, 'result' => $result];
        $this->taskCounter = $this->taskCounter + 1;
    }

    public function run()
    {
        $this->generateObjectiveTasks();
        while (true) {
            $currentTask = array_shift($this->taskList);

            $result = $this->executeTask($currentTask);
            $this->saveCompletedTask($currentTask, $result);


            $newTasks = $this->createNewTasks($result);
            $taskList = $this->appendNewTasks($newTasks);
            $this->prioritizeTasks();
            $this->displayResults($currentTask, $result);

            sleep(3);
            if ($this->taskCounter > 10) {
                break;
            }
        }
    }

    public function displayResults($task, $result)
    {
        echo "<h4>" . var_dump($task) . "</h4>";
        echo "<p>" . $result . "</p>";
    }

    protected function storeTask($task)
    {
        $this->taskList[] = $task;
    }

    protected function fetchCompletedTasks()
    {
        if (count($this->completedTasks)) {
            return "Your previous completed tasks are : " . json_encode($this->completedTasks);
        }
        return NULL;
    }

    protected function executeTask($task)
    {
        $taskPrompt = $this->constructTaskPrompt($task);
        $result = $this->sendRequest($taskPrompt);
        return $result;
    }

    protected function constructTaskPrompt($task)
    {
        $contextPrompt = $this->constructContextPrompt();
        $prompt = "You are an AI who performs one task based on the following objective: {" . $this->objective . "}\n" . $contextPrompt . "\nYour task: {" . $task . "}\nResponse:";
        return $prompt;
    }

    protected function constructContextPrompt()
    {
        $context = $this->getContext();
        return count($context) ? "Take into account these previously completed tasks: {" . json_encode($context) . "}\n." : "";
    }

    protected function getContext()
    {
        return $this->completedTasks;
    }



    protected function fetchObjective()
    {
        return "Your objective is " . $this->objective;
    }


    protected function fetchIncompleteTasks()
    {
        if (count($this->getContext())) {
            return "These are incomplete tasks: " . json_encode($this->taskList);
        }
        return NULL;
    }


    protected function fetchLastTask()
    {
        if (count($this->getContext())) {
            $lastTask = end($this->context);
            return "Your last task was " . json_encode($lastTask);
        }
        return NULL;
    }



    protected function createNewTasks()
    {
        $prompt = "You are a task creation AI that uses the result of an execution agent to create new tasks with the following objective.";
        $prompt .= $this->fetchObjective();
        $prompt .= $this->fetchLastTask();
        $prompt .= $this->fetchIncompleteTasks();
        $prompt .= "Based on the result, create new tasks to be completed by the AI system that do not overlap with incomplete tasks.\n";
        $prompt .= "Return as a valid JSON list containing the new tasks. Do not add any pre and post texts. Only the valid json.";
        $response = $this->sendRequest($prompt);

        $tasks = json_decode($response, true);
        $newTasks = [];
        foreach ($tasks as $index => $task) {
            if (!empty($task)) {
                $newTasks[] = $task;
            }
        }
        return $newTasks;
    }


    protected function prioritizeTasks()
    {


        $prompt = "You are a task prioritization AI tasked with cleaning the formatting of and reprioritizing the following task list : " . json_encode($this->taskList);
        $prompt .= "\nConsider the ultimate objective of your team is : " . $this->objective;
        $prompt .= $this->fetchLastTask();
        $prompt .= "Now reprioritize all the tasks based on this\n";
        //$prompt .= $this->fetchCompletedTasks();
        $prompt .= "Do not remove any tasks. Return the reprioritized tasks as a valid JSON list. Do not add any pre or post explanation texts. The JSON should only list the task-description. Do not add named keys on the JSON, just the list of descriptions.";
        $response = $this->sendRequest($prompt);
        $tasks = json_decode($response, true);
        $newTaskList = [];
        foreach ($tasks as $task) {
            $newTaskList[] = $task;
        }
        return $newTaskList;
    }

    protected function sendRequest($prompt)
    {

        $request_data = [
            'model' => 'text-davinci-003',
            'prompt' =>  $prompt,
            "temperature" => 0.9,
            "max_tokens" => 2000,
            "top_p" => 1,
            "frequency_penalty" => 0,
            "presence_penalty" => 0
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ));

        $response = curl_exec($ch);
        curl_close($ch);

        $response_data = json_decode($response, true);
        return $response_data['choices'][0]['text'];
    }
}
