<?PHP
	// =======================================================
	// MODELO DE ORIENTAÇÃO A OBJETOS PARA AGRIMENSURA EM PHP
	// =======================================================

	final class ProjetoGlobal {
		// Constantes imutáveis
		public const NOME = "Levantamento Topográfico – Lote 45";
		public const AUTOR = "Wagner Dias de Souza";
		public const VERSAO = "1.0.0";
		public const DESCRICAO = "Projeto de levantamento planialtimétrico com fins de regularização fundiária.";
		public const DATA_CRIACAO = "2025-05-09";

		// Método auxiliar opcional
		public static function infoCompleta(): string {
			return sprintf(
				"%s\nAutor: %s\nVersão: %s\nData: %s\nDescrição: %s",
				self::NOME,
				self::AUTOR,
				self::VERSAO,
				self::DATA_CRIACAO,
				self::DESCRICAO
			);
		}
	}
	/**
	 * Representa um ponto com coordenadas geográficas e altitude.
	 */
	class PontoGeografico {
		private float $latitude;
		private float $longitude;
		private float $altitude;
		public int $id;

		public function __construct(float $latitude, float $longitude, float $altitude = 0.0) {
			$this->setLatitude($latitude);
			$this->setLongitude($longitude);
			$this->setAltitude($altitude);
		}

		public function getLatitude(): float {
			return $this->latitude;
		}

		public function getLongitude(): float {
			return $this->longitude;
		}

		public function getAltitude(): float {
			return $this->altitude;
		}

		public function setLatitude(float $latitude): void {
			if ($latitude < -90 || $latitude > 90) {
				throw new InvalidArgumentException("Latitude inválida.");
			}
			$this->latitude = $latitude;
		}

		public function setLongitude(float $longitude): void {
			if ($longitude < -180 || $longitude > 180) {
				throw new InvalidArgumentException("Longitude inválida.");
			}
			$this->longitude = $longitude;
		}

		public function setAltitude(float $altitude): void {
			$this->altitude = $altitude;
		}

		public function descrever(): string {
			return "Lat: {$this->latitude}, Lon: {$this->longitude}, Alt: {$this->altitude} m";
		}
	}

	/**
	 * Herda de PontoGeografico e adiciona informação de precisão da medição.
	 */
	class PontoMedido extends PontoGeografico {
		private float $precisao;

		public function __construct(float $latitude, float $longitude, float $altitude, float $precisao) {
			parent::__construct($latitude, $longitude, $altitude);
			$this->precisao = $precisao;
		}

		public function getPrecisao(): float {
			return $this->precisao;
		}

		public function setPrecisao(float $precisao): void {
			$this->precisao = $precisao;
		}

		public function descrever(): string {
			return parent::descrever() . " (±{$this->precisao} m)";
		}
	}

	/**
	 * Define uma interface para exportar dados geográficos.
	 */
	interface Exportavel {
		public function exportar(array $pontos): string;
	}

	/**
	 * Exporta os dados em formato CSV.
	 */
	class ExportadorCSV implements Exportavel {
		public function exportar(array $pontos): string {
			$saida = "Latitude,Longitude,Altitude\n";
			foreach ($pontos as $ponto) {
				$saida .= "{$ponto->getLatitude()},{$ponto->getLongitude()},{$ponto->getAltitude()}\n";
			}
			return $saida;
		}
	}

	/**
	 * Exporta os dados em formato JSON.
	 */
	class ExportadorJSON implements Exportavel {
		public function exportar(array $pontos): string {
			$dados = [];
			foreach ($pontos as $ponto) {
				$dados[] = [
					'latitude' => $ponto->getLatitude(),
					'longitude' => $ponto->getLongitude(),
					'altitude' => $ponto->getAltitude()
				];
			}
			return json_encode($dados, JSON_PRETTY_PRINT);
		}
	}

	/**
	 * Abstração de um instrumento que mede distâncias entre pontos.
	 */
	abstract class InstrumentoTopografico {
		protected string $marca;

		public function __construct(string $marca) {
			$this->marca = $marca;
		}

		abstract public function medirDistancia(PontoGeografico $a, PontoGeografico $b): float;

		public function getMarca(): string {
			return $this->marca;
		}
	}

	/**
	 * Cálculo de distância via fórmula de Haversine (GPS).
	 */
	class ReceptorGPS extends InstrumentoTopografico {
		public function medirDistancia(PontoGeografico $a, PontoGeografico $b): float {
			$raioTerra = 6371000; // em metros
			$dLat = deg2rad($b->getLatitude() - $a->getLatitude());
			$dLon = deg2rad($b->getLongitude() - $a->getLongitude());
			$lat1 = deg2rad($a->getLatitude());
			$lat2 = deg2rad($b->getLatitude());

			$aCalc = sin($dLat/2)**2 + cos($lat1) * cos($lat2) * sin($dLon/2)**2;
			$c = 2 * atan2(sqrt($aCalc), sqrt(1 - $aCalc));

			return $raioTerra * $c;
		}
	}

	/**
	 * Cálculo plano de distância (Estação Total).
	 */
	class EstacaoTotal extends InstrumentoTopografico {
		public function medirDistancia(PontoGeografico $a, PontoGeografico $b): float {
			$dx = ($b->getLongitude() - $a->getLongitude()) * 111320;
			$dy = ($b->getLatitude() - $a->getLatitude()) * 110540;
			$dz = $b->getAltitude() - $a->getAltitude();

			return sqrt($dx**2 + $dy**2 + $dz**2);
		}

		public function calcularAzimute(PontoGeografico $a, PontoGeografico $b): float {
			$dx = ($b->getLongitude() - $a->getLongitude()) * 111320;
			$dy = ($b->getLatitude() - $a->getLatitude()) * 110540;

			$azimuteRad = atan2($dx, $dy);
			$azimuteDeg = rad2deg($azimuteRad);
			return ($azimuteDeg + 360) % 360; // Normaliza entre 0° e 360°
		}
	}

	/**
	 * Composição: um projeto pode conter vários pontos.
	 */
	class ProjetoTopografico {
		private string $nome;
		private array $pontos = [];

		public function __construct(string $nome) {
			$this->nome = $nome;
		}

		public function adicionarPonto(PontoGeografico $ponto): void {
			$this->pontos[] = $ponto;
		}

		public function listarPontos(): void {
			echo "Projeto: {$this->nome}\n";
			foreach ($this->pontos as $indice => $ponto) {
				echo "Ponto " . ($indice + 1) . ": " . $ponto->descrever() . PHP_EOL;
			}
		}

		public function getTodosPontos(): array {
			return $this->pontos;
		}

		public function exportarDados(Exportavel $exportador): string {
			return $exportador->exportar($this->pontos);
		}
	}
?>