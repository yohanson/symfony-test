<?php

namespace App\Entity;

use App\Repository\SaleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SaleRepository::class)]
class Sale
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $date = null;

    /**
     * @var Collection<int, SaleEntry>
     */
    #[ORM\OneToMany(targetEntity: SaleEntry::class, mappedBy: 'sale', orphanRemoval: true)]
    private Collection $saleEntries;

    public function __construct(?int $id)
    {
        $this->id = $id;
        $this->saleEntries = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): static
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @return Collection<int, SaleEntry>
     */
    public function getSaleEntries(): Collection
    {
        return $this->saleEntries;
    }

    public function addSaleEntry(SaleEntry $saleEntry): static
    {
        if (!$this->saleEntries->contains($saleEntry)) {
            $this->saleEntries->add($saleEntry);
            $saleEntry->setSale($this);
        }

        return $this;
    }

    public function removeSaleEntry(SaleEntry $saleEntry): static
    {
        if ($this->saleEntries->removeElement($saleEntry)) {
            // set the owning side to null (unless already changed)
            if ($saleEntry->getSale() === $this) {
                $saleEntry->setSale(null);
            }
        }

        return $this;
    }
}
