<?php
class Participant{
    private $name;
    private $image;
    private $gender;
    private $rank;
    private $year;
    private $programs;
    private $state;
    private $id;

    public function setID($dbID){ $this->id = $dbID; }
    public function setName($nameName){ $this->name = $nameName; }
    public function getName(){ print_r( 'Name: '.$this->name . '<br>'); }
    public function setImage($imageImage){ $this->image = $imageImage; }
    public function getImage(){ print_r( 'Image: '.$this->image . '<br>'); }
    public function setGender($genderGender){ $this->gender = $genderGender; }
    public function getGender(){ print_r('Gender: '.$this->gender . '<br>'); }
    public function setRank($rankNumber){ $this->rank = $rankNumber; }
    public function getRank(){ print_r('Rank: '.$this->rank . '<br>'); }
    public function setYear($birthYear){ $this->year = $birthYear; }
    public function getYear(){ print_r('Year: '.$this->year . '<br>'); }
    public function setState($stateState){ $this->state = $stateState; }
    public function getState(){ print_r('State: '.$this->state . '<br>'); }
    public function setPrograms($programs){ 
        $this->programs = str_getcsv($programs);
    }
    public function getPrograms(){ 
        for($j=0; $j<count($this->programs); $j++){
            print_r('<a href="list_participant.php?program='.$this->programs[$j].'">Program #'.($j+1).' is '.$this->programs[$j].'</a><br>');
        }
    }
    public function getNameLink(){
        $anchor = '<a href="show_participant.php?id='.$this->id.'">'.$this->name.'</a><br>';
        print_r($anchor);
        $this->getPrograms();
    }
    public function getImages(){
        $anchor = '<p>
        <b>Image:</b> <?php echo $image;?><br>
        <img src="<?php echo $image;?>">
        </p>';
        print_r($anchor);
        $this->getImages();
    }
    //->setData runs all the setX methods
    //$data_row is a single row of data from the csv passed as an array. Mappings are below.
    public function setData($data_row){
        // mapping of data_row:    name=3
        //                         gender=2
        //                         rank=0
        //                         year=1
        //                         program=4
        //                         state=5
        $this->setName($data_row[1]);
        $this->setGender($data_row[3]);
        $this->setImages($data_row[2]);
        $this->setRank($data_row[0]);
        $this->setYear($data_row[4]);
        $this->setPrograms($data_row[6]);
        $this->setState($data_row[5]);
}

    //->getData runs all the getX methods (which print out the data for each property)
    public function getData(){
        $this->getRank();
        $this->getName();
        $this->getImages();
        $this->getGender();
        $this->getYear();
        $this->getState();
        $this->getPrograms();
    }

    public function save(){
        global $pdo;

        try{
            $participant_insert = $pdo->prepare("INSERT INTO participant (number, name, gender, year, state)
                                            VALUES (?, ?, ?, ?, ?)");
            $db_participant = $participant_insert->execute([$this->rank, $this->name, $this->gender, $this->year, $this->state]);
            $this->id = $pdo->lastInsertId();
            print_r("--Saved $this->name to the database.--<br>\n");

            $select_program = $pdo->prepare("SELECT * FROM program WHERE name = ?");
            $program_insert = $pdo->prepare("INSERT INTO program (name) VALUES (?)");
            $program_link = $pdo->prepare("INSERT INTO participant_program (participant_id, program_id) VALUES (?, ?)");

            for($i=0; $i<count($this->programs); $i++){
                $select_program->execute([$this->programs[$i]]);
                $existing_program = $select_program->fetch();
                if(!$existing_program){
                    $db_program = $program_insert->execute([$this->programs[$i]]);
                    $program_id = $pdo->lastInsertID();
                } else {
                    $program_id = $existing_program['program_id'];
                }
                $program_link->execute([$this->id, $program_id]);
                print_r("Connected ".$this->programs[$i]." to $this->name<br>\n");
            }
            flush();
            
        } catch (PDOException $e){
            print_r("Error saving participant to database: ".$e->getMessage() . "<br>\n");
            exit;
        }
    }

    static public function load_by_id($id){
        global $pdo;

        try{
            $find_participant = $pdo->prepare("SELECT * FROM participant
                                            WHERE participant_id = ?");
            $select_program = $pdo->prepare("SELECT program.name AS name
                                                FROM participant_program, program
                                                WHERE participant_program.participant_id = ?
                                                AND participant_program.program_id = program.program_id");
            $find_participant->execute([$id]);
            $db_participant = $find_participant->fetch();
            if(!$db_participant){
                return false;
            } else {
                $participant = new participant();
                $participant->setRank($db_participant['number']);
                $participant->setName($db_participant['name']);
                $participant->setgender($db_participant['gender']);
                $participant->setYear($db_participant['year']);
                $participant->setState($db_participant['state']);
                $participant->setID($id);

                $select_program->execute([$id]);
                $db_programs = $select_program->fetchAll();
                $programs = array();
                for($j=0; $j<count($db_programs); $j++){
                    array_push($programs, $db_programs[$j]['name']);
                }
                $participant->setprograms(implode(',', $programs));
                return $participant;                
            }
        } catch (PDOException $e){
            print_r("Error reading single participant from database: ".$e->getMessage() . "<br>\n");
            exit;
        }
    }

    static public function load($program=false){
        global $pdo;

        $participants = array();
        try{
            if($program==false){
                $select_participants = $pdo->prepare("SELECT * FROM participant ORDER BY number ASC");
                $select_participants->execute();
            } else {
                $select_participants = $pdo->prepare("SELECT participant.* FROM participant, participant_program, program
                                                WHERE participant.participant_id = participant_program.participant_id AND
                                                  participant_program.program_id = program.program_id AND
                                                  program.name = ?
                                                ORDER BY participant.number ASC");
                $select_participants->execute([$program]);
            }
            
            $select_program = $pdo->prepare("SELECT program.name AS name
                                            FROM participant_program, program
                                            WHERE participant_program.participant_id = ?
                                              AND participant_program.program_id = program.program_id");


            $db_participants = $select_participants->fetchAll();

            for($i=0; $i<count($db_participants); $i++){
                $participant = new participant();
                $participant->setGender($db_participants[$i]['gender']);
                $participant->setYear($db_participants[$i]['year']);
                $participant->setRank($db_participants[$i]['number']);
                $participant->setName($db_participants[$i]['name']);
                $participant->setState($db_participants[$i]['state']);
                $participant->setID($db_participants[$i]['participant_id']);

                $select_program->execute([$participant->id]);
                $db_programs = $select_program->fetchAll();
                $programs = array();
                for($j=0; $j<count($db_programs); $j++){
                    array_push($programs, $db_programs[$j]['name']);
                }
                $participant->setprograms(implode(',', $programs));
                array_push($participants, $participant);
            }
            return $participants;
        } catch (PDOException $e){
            print_r("Error reading participant from database: ".$e->getMessage() . "<br>\n");
            exit;
        }
    }
}
?>