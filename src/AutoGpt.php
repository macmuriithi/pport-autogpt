<?php

namespace Pport\Gpt;

class AutoGpt
{
    protected $apiKey;
    protected $objective;
    protected $taskList;
    protected $context;

    public function __construct($objective, $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->objective = $objective;
        $this->taskList = $this->generateObjectiveTasks($objective);
        $this->context = [];
    }

    public function executeTasks()
    {
        while (true) {
            $currentTask = array_shift($this->taskList);
            $result = $this->executeTask($currentTask['description']);
            $this->context[$currentTask['name']] = $result;
            $newTasks = $this->createNewTasks($result);
            $this->taskList = array_merge($this->taskList, $newTasks);
            $this->taskList = $this->prioritizeTasks($currentTask['name']);
            $this->displayResults($currentTask['name'], $result);
            sleep(3);
            if (count($this->context) > 10) {
                break;
            }
        }
    }

    protected function displayResults($task, $result)
    {
        echo $task . " " . $result . "<br/><br/>";
    }

    protected function generateObjectiveTasks($objective)
    {
        $prompt = "Objective: $objective\nTask: Generate initial tasks\n";
        $tasks = $this->sendRequest($prompt);

        $tasks = explode("\n", $tasks);
        $taskList = [];
        foreach ($tasks as $index => $task) {
            if (!empty($task)) {
                $taskList[] = ['name' => "Task " . ($index + 1), 'description' => $task];
            }
        }
        return $taskList;
    }

    protected function executeTask($taskDescription)
    {
        $prompt = "Task: $taskDescription\nObjective: Complete the task\n";
        $result = $this->sendRequest($prompt);
        return $result;
    }

    protected function createNewTasks($result)
    {
        $prompt = "Objective: $this->objective\nTask: Generate new tasks based on the results\n";
        foreach ($this->context as $taskName => $taskResult) {
            $prompt .= "Task: $taskName\nResult: $taskResult\n";
        }
        $prompt .= "Results: $result\n";
        $response = $this->sendRequest($prompt);

        $tasks = explode("\n", $response);
        $newTasks = [];
        foreach ($tasks as $index => $task) {
            if (!empty($task)) {
                $newTasks[] = ['name' => "Task " . (count($this->taskList) + count($newTasks) + 1), 'description' => $task];
            }
        }
        return $newTasks;
    }


    protected function prioritizeTasks($currentTaskName)
    {
        $prompt = "Objective: $this->objective\nTask: Reprioritize tasks based on $currentTaskName\n";
        foreach ($this->taskList as $index => $task) {
            $prompt .= ($index + 1) . "{$task['name']}\n";
        }
        $taskIndexes = $this->sendRequest($prompt);
        $taskIndexes = explode("\n", $taskIndexes);
        $newTaskList = [];
        foreach ($taskIndexes as $taskIndex => $task) {
            if (!empty($taskIndex)) {
                $newTaskList[] = $this->taskList[$taskIndex - 1];
            }
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
