<?php
namespace revivalpmmp\pureentities\task\spawners\monster;


use pocketmine\level\generator\biome\Biome;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\Player;
use revivalpmmp\pureentities\entity\monster\walking\Enderman;
use revivalpmmp\pureentities\PureEntities;
use revivalpmmp\pureentities\task\spawners\BaseSpawner;

/**
 * Class EndermanSpawner
 *
 * Spawn: Endermen spawn on solid blocks at light level 7 or less. They spawn commonly in the End in packs
 * (or "hauntings") of 4. In the Overworld they spawn in groups of 1-4 and are rarer than most other mobs.
 * In the Nether they spawn in groups of 4 and are even rarer.
 *
 * @package revivalpmmp\pureentities\task\spawners
 */
class EndermanSpawner extends BaseSpawner {

    public function spawn (Position $pos, Player $player) : bool {
        if ($this->spawnAllowedByProbability()) { // first check if spawn would be allowed, if not the other method calls make no sense at all
            $block = $pos->level->getBlock($pos); // because we get the air block, we need to substract 1 from the y position
            $biomeId = $pos->level->getBiomeId($pos->x, $pos->z);

            $herdSize = $biomeId == Biome::HELL ? 4 : mt_rand(1, 4);

            PureEntities::logOutput($this->getClassNameShort() .
                ": isNight: " . !$this->isDay($pos->getLevel()) .
                ", block is solid: " . $block->isSolid() . "[" . $block->getName() .
                "], spawnAllowedByEntityCount: " . $this->spawnAllowedByEndermanCount($pos->getLevel(), $herdSize) .
                ", playerDistanceOK: " . $this->checkPlayerDistance($player, $pos),
                PureEntities::DEBUG);

            if (!$this->isDay($pos->getLevel()) and // only spawn at night ...
                $block->isSolid() and // spawn only on solid blocks
                $this->spawnAllowedByEndermanCount($pos->getLevel(), $herdSize) and // respect count in level
                $this->checkPlayerDistance($player, $pos)) { // distance to player has to be at least a configurable amount of blocks (atm 8!)
                for ($i=0; $i < $herdSize; $i++) {
                    $this->spawnEntityToLevel($pos, $this->getEntityNetworkId(), $pos->getLevel(), "Monster");
                    PureEntities::logOutput($this->getClassNameShort() . ": scheduleCreatureSpawn (pos: $pos)", PureEntities::NORM);
                }
                return true;
            }
        } else {
            PureEntities::logOutput($this->getClassNameShort() . ": spawn not allowed because probability denies spawn", PureEntities::DEBUG);
        }

        return false;
    }

    protected function getEntityNetworkId () : int {
        return Enderman::NETWORK_ID;
    }
    protected function getEntityName () : string {
        return "Enderman";
    }


    // ---- enderman spawner specific -----

    /**
     * Special method because we spawn herds of rabbits (at least 2 of them)
     *
     * @param Level $level
     * @param int $herdSize
     * @return bool
     */
    protected function spawnAllowedByEndermanCount (Level $level, int $herdSize) : bool {
        if ($this->maxSpawn <= 0) {
            return false;
        }
        $count = 0;
        foreach ($level->getEntities() as $entity) { // check all entities in given level
            if ($entity->isAlive() and !$entity->closed and $entity::NETWORK_ID == $this->getEntityNetworkId()) { // count only alive, not closed and desired entities
                $count ++;
            }
        }

        PureEntities::logOutput($this->getClassNameShort() . ": got count of  $count entities living for " . $this->getEntityName(), PureEntities::DEBUG);

        if (($count + $herdSize) < $this->maxSpawn) {
            return true;
        }
        return false;
    }

}