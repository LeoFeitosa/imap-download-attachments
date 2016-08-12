set_time_limit(3000); 
$imapCaminho = '{mail.email.com:110/pop3/novalidate-cert}INBOX';
$usuario 	 = 'email@email.com';
$senha 		 = 'pass';

/* try to connect */
$inbox = imap_open($imapCaminho,$usuario,$senha) or die('Não é possivel conectar ao e-mail: ' . imap_last_error());

/* Obter todos os novos e-mails. Se definido como " ALL " em vez
* De 'novo' recupera todos os e-mails , mas pode ser
* Uso intensivo de recursos , de modo a seguinte variável ,
* $ Max_emails , coloca o limite no número de e-mails baixados.
*
*/
$emails = imap_search($inbox, 'ALL');

/* Útil apenas se a pesquisa acima é definido como ' ALL' */
$max_emails = 1;/*1 para o ultimo email enviado*/


/* se qualquer e-mails é encontrado, percorre cada e-mail */
if($emails) {
	
	$count = 1;
	
	/* colocar os e-mails mais novos no topo*/
	rsort($emails);
	
	/* passa pelos emails */
	foreach($emails as $email_number) 
	{

		/* obter informações específicas a este e-mail */
		$overview = imap_fetch_overview($inbox,$email_number,0);
		
		/* pega mensagem do email */
		$message = imap_fetchbody($inbox,$email_number,2);
		
		/* pega estrutura do email */
		$structure = imap_fetchstructure($inbox, $email_number);

		$attachments = array();
		
		/* se é encontrado algum anexo... */
		if(isset($structure->parts) && count($structure->parts)) 
		{
			for($i = 0; $i < count($structure->parts); $i++) 
			{
				$attachments[$i] = array(
					'is_attachment' => false,
					'filename' => '',
					'name' => '',
					'attachment' => ''
					);
				
				if($structure->parts[$i]->ifdparameters) 
				{
					foreach($structure->parts[$i]->dparameters as $object) 
					{
						if(strtolower($object->attribute) == 'filename') 
						{
							$attachments[$i]['is_attachment'] = true;
							$attachments[$i]['filename'] = $object->value;
						}
					}
				}
				
				if($structure->parts[$i]->ifparameters) 
				{
					foreach($structure->parts[$i]->parameters as $object) 
					{
						if(strtolower($object->attribute) == 'name') 
						{
							$attachments[$i]['is_attachment'] = true;
							$attachments[$i]['name'] = $object->value;
						}
					}
				}
				
				if($attachments[$i]['is_attachment']) 
				{
					$attachments[$i]['attachment'] = imap_fetchbody($inbox, $email_number, $i+1);
					
					/* 4 = QUOTED-PRINTABLE encoding */
					if($structure->parts[$i]->encoding == 3) 
					{ 
						$attachments[$i]['attachment'] = base64_decode($attachments[$i]['attachment']);
					}
					/* 3 = BASE64 encoding */
					elseif($structure->parts[$i]->encoding == 4) 
					{ 
						$attachments[$i]['attachment'] = quoted_printable_decode($attachments[$i]['attachment']);
					}
				}
			}
		}
		
		/* percorre cada anexo e salva */
		foreach($attachments as $attachment)
		{
			if($attachment['is_attachment'] == 1)
			{
				$filename = $attachment['name'];
				if(empty($filename)) $filename = $attachment['filename'];
				
				if(empty($filename)) $filename = time() . ".dat";
				
                /* 
				*prefixar o número de e-mail e data de download para o nome do arquivo no caso de dois e-mails
				* Tem o anexo com o mesmo nome do arquivo
                */
				$fp = fopen("./assets/" . $email_number . "_" . date("d-m-Y") . "_" . $filename, "w+");
				fwrite($fp, $attachment['attachment']);
				fclose($fp);
			}
			
		}
		
		if($count++ >= $max_emails) break;
	}
	
} 

/* fechar a conexão */
imap_expunge($inbox);
imap_close($inbox);