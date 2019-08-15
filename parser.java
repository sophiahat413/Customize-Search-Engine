import java.io.File;
import java.io.FileInputStream;
import java.io.FileWriter;
import java.io.IOException;

import org.apache.tika.exception.TikaException;
import org.apache.tika.metadata.Metadata;
import org.apache.tika.parser.ParseContext;
import org.apache.tika.parser.html.HtmlParser;
import org.apache.tika.sax.BodyContentHandler;
import org.xml.sax.SAXException;


public class parser {
	public static void main(final String[] args) throws IOException,SAXException, TikaException {

	      //detecting the file type
	 
	      File dir = new File("/Users/sophiahat/Documents/School/Web Search Engine/HW/HW4/solr-8.0.0/reutersnews");
	      String des = "/Users/sophiahat/Documents/School/Web Search Engine/HW/HW5/big_new2.txt";
	      FileWriter writer = new FileWriter(des);
	      for (File file : dir.listFiles()) {
	    	  BodyContentHandler handler = new BodyContentHandler(-1);
		      Metadata metadata = new Metadata();
	    	  String fileName = "/Users/sophiahat/Documents/School/Web Search Engine/HW/HW4/solr-8.0.0/reutersnews/" + file.getName();		
	    	  System.out.println(fileName);
	    	  FileInputStream inputstream = new FileInputStream(new File(fileName));
	    	  ParseContext pcontext = new ParseContext();
		      //Html parser 
		      HtmlParser htmlparser = new HtmlParser();
		      htmlparser.parse(inputstream, handler, metadata,pcontext);
		      String[] metadataNames = metadata.names();
		      //System.out.println("Contents of the document:" + handler.toString());
		      writer.append(handler.toString() + "\n");
		      //for(String name : metadataNames) {
		    	  //writer.append(name + " : " + metadata.get(name) + "\n");  
		      //}
	      }
	      writer.flush();
		  writer.close();
	  }
}


