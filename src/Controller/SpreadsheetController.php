<?php
namespace App\Controller;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\Sale;
use App\Entity\SaleEntry;
use App\Exception\SpreadsheetFormatException;
use DateTimeImmutable;
use Doctrine\DBAL\Types\DateImmutableType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use \PhpOffice\PhpSpreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SpreadsheetController extends AbstractController
{
    const SPREADSHEET_FORMAT = [
        'products' => [
            'product_id',
            'product_name',
            'category_id',
            'price',
        ],
        'categories' => [
            'category_id',
            'category_name',
        ],
        'sales' => [
            'sale_id',
            'product_id',
            'quantity',
            'sale_date',
        ]
    ];

    protected EntityManagerInterface $entityManager;

    #[Route('/spreadsheet/new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->entityManager = $entityManager;
        $form = $this->createFormBuilder()
            ->setMethod('POST')
            ->add('file', FileType::class, ['label' => 'Spreadsheet'])
            ->add('upload', SubmitType::class, ['label' => 'Upload'])
            ->getForm();

        if ($request->isMethod('POST')) {
            /** @var UploadedFile $file */
            $file = $request->files->get('form')['file'];
            try {
                $this->entityManager->beginTransaction();
                $this->importSpreadSheet($file->getRealPath());
                $this->entityManager->commit();
            } catch (SpreadsheetFormatException $e) {
                $this->entityManager->rollback();
                return new Response(
                    "Spreadsheet does not meet the expected format.\n" . $e->getMessage(),
                    Response::HTTP_NOT_ACCEPTABLE
                );
            } catch (\Throwable $e) {
                $this->entityManager->rollback();
                throw $e;
            } finally {
                unlink($file->getRealPath());
            }

            return $this->redirectToRoute('app_homepage');
        }

        return $this->render('/spreadsheet/form.html.twig', [
            'form' => $form,
        ]);
    }

    protected function importSpreadSheet(string $filename)
    {
        $spreadsheet = PhpSpreadsheet\IOFactory::load($filename);
        static::checkSpreadsheetFormat($spreadsheet);
        $categories = $spreadsheet->getSheetByName('categories');
        $this->importCategories($categories);
        $products = $spreadsheet->getSheetByName('products');
        $this->importProducts($products);
        $sales = $spreadsheet->getSheetByName('sales');
        $this->importSales($sales);
        $this->entityManager->flush();
    }

    protected function importCategories(PhpSpreadsheet\Worksheet\Worksheet $sheet): void
    {
        $headers = static::getHeaders($sheet);
        $headerIndexes = array_flip($headers);
        foreach ($sheet->getRowIterator(2) as $row) {
            $values = [];
            foreach ($row->getCellIterator() as $cell) {
                $values[] = $cell->getValue();
            }
            $id = intval($values[$headerIndexes['category_id']]);
            $name = $values[$headerIndexes['category_name']];
            $category = new Category($id, $name);
            $this->entityManager->persist($category);
        }
    }

    protected function importProducts(PhpSpreadsheet\Worksheet\Worksheet $sheet): void
    {
        $headers = static::getHeaders($sheet);
        $headerIndexes = array_flip($headers);
        foreach ($sheet->getRowIterator(2) as $row) {
            $values = [];
            foreach ($row->getCellIterator() as $cell) {
                $values[] = $cell->getValue();
            }
            $id = $values[$headerIndexes['product_id']];
            $name = $values[$headerIndexes['product_name']];
            $categoryId = intval($values[$headerIndexes['category_id']]);
            $price = $values[$headerIndexes['price']];
            $category = $this->entityManager->find(Category::class, $categoryId);
            $product = new Product($id, $name, $category, $price);
            $this->entityManager->persist($product);
        }
    }

    protected function importSales(PhpSpreadsheet\Worksheet\Worksheet $sheet): void
    {
        $headers = static::getHeaders($sheet);
        $headerIndexes = array_flip($headers);
        $prevSaleId = null;
        $sale = null;
        foreach ($sheet->getRowIterator(2) as $row) {
            $values = [];
            foreach ($row->getCellIterator() as $cell) {
                $values[] = $cell->getValue();
            }
            $saleId = intval($values[$headerIndexes['sale_id']]);
            $productId = intval($values[$headerIndexes['product_id']]);
            $quantity = intval($values[$headerIndexes['quantity']]);
            $saleDate = new DateTimeImmutable($values[$headerIndexes['sale_date']]);
            if ($saleId !== $prevSaleId) {
                if (isset($sale)) {
                    $this->entityManager->persist($sale);
                }
                $sale = new Sale($saleId);
                $sale->setDate($saleDate);
                $prevSaleId = $saleId;
            }
            $product = $this->entityManager->find(Product::class, $productId);
            $saleEntry = new SaleEntry();
            $saleEntry->setProduct($product);
            $saleEntry->setQuantity($quantity);
            $saleEntry->setSale($sale);
            $this->entityManager->persist($saleEntry);
            $sale->addSaleEntry($saleEntry);
        }
        $this->entityManager->persist($sale);
    }

    protected function checkSpreadsheetFormat(Spreadsheet $spreadsheet): void
    {
        $expectedSheets = array_keys(static::SPREADSHEET_FORMAT);
        $actualSheets = $spreadsheet->getSheetNames();
        if (!static::allInArray($expectedSheets, $actualSheets)) {
            throw new SpreadsheetFormatException(
                "Cannot find all required worksheets.\n" .
                "Expected: " . join(', ', $expectedSheets) . ".\n" .
                "Actual: " . join(', ', $actualSheets) . '.'
            );
        }
        foreach ($expectedSheets as $sheetName) {
            $sheet = $spreadsheet->getSheetByName($sheetName);
            $expectedHeaders = static::SPREADSHEET_FORMAT[$sheetName];
            $actualHeaders = static::getHeaders($sheet);
            if (!static::allInArray($expectedHeaders, $actualHeaders)) {
                throw new SpreadsheetFormatException(
                    "Cannot find all required columns on sheet '$sheetName'.\n" .
                    "Expected: " . join(', ', $expectedHeaders) . ".\n" .
                    "Actual: " . join(', ', $actualHeaders) . '.'
                );
            }
        }
    }

    protected static function allInArray(array $needles, array $haystack): bool
    {
        $lowcaseHaystack = array_map('strtolower', $haystack);
        foreach ($needles as $needle) {
            if (!in_array(strtolower($needle), $lowcaseHaystack)) {
                return false;
            }
        }
        return true;
    }

    protected static function getHeaders(Worksheet $sheet): array
    {
        $headers = [];
        foreach ($sheet->getColumnIterator() as $column) {
            $columnIndex = Coordinate::columnIndexFromString($column->getColumnIndex());
            $headers[] = $sheet->getCell([$columnIndex, 1])->getValue();
        }
        return array_filter($headers);
    }
}
