
import java.io.BufferedInputStream;
import java.io.BufferedOutputStream;
import java.io.File;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;

import junit.framework.Test;
import junit.framework.TestCase;
import junit.framework.TestSuite;

import com.meterware.httpunit.GetMethodWebRequest;
import com.meterware.httpunit.HTMLElement;
import com.meterware.httpunit.SubmitButton;
import com.meterware.httpunit.WebConversation;
import com.meterware.httpunit.WebForm;
import com.meterware.httpunit.WebRequest;
import com.meterware.httpunit.WebResponse;

/**
 * Test class for Cartoweb HttpUnit.
 * 
 * @author sypasche
 */
public class CartowebTest extends TestCase
{

	/** Directory where to save html fetched files */
	static final String HTML_SAVE_DIRECTORY = "/tmp/validator_tmp/";

	static String URL;

	public CartowebTest( String name )
	{
		super( name );
	}

	public static void main( String args[] )
	{
		if ( args.length != 1 )
		{
			System.out.println( "Usage: CartowebTest CARTOCLIENT_URL\n" );
			System.exit( 0 );
		}
		CartowebTest.URL = args[ 0 ];

		junit.textui.TestRunner.run( suite() );
	}

	public static Test suite()
	{
		if ( URL == null ) URL = System.getProperty( "cartoclient_url" );
		if ( URL == null ) throw new RuntimeException( "Please invoke this test via main(), with URL argument" );

		return new TestSuite( CartowebTest.class );
	}

	/**
	 * Saves a response html into a directory.
	 * 
	 * @param currentPage
	 * @param string
	 */
	private void saveAs( WebResponse currentPage, String string ) throws Exception
	{
		File htmlDirectory = new File(HTML_SAVE_DIRECTORY);
		if (!htmlDirectory.exists())
			htmlDirectory.mkdirs();
		File outputFile = new File( HTML_SAVE_DIRECTORY + string );
		OutputStream os = new FileOutputStream( outputFile );
		os.write( currentPage.getText().getBytes() );
		os.close();
	}

	/**
	 * Checks that the html generated response is Xhtml valid.
	 * 
	 * @param response
	 * @throws Exception
	 */
	private void checkXHTMLValidity( WebResponse response ) throws Exception
	{

		Process process = null;
		try
		{
			process = Runtime.getRuntime().exec(
   new String[] { "xmllint", "--encode", "iso-8859-1", "--valid", "--nonet", "--noout", "--postvalid", "-" } );
		}
		catch ( IOException ioe )
		{
			fail( "Problem which launching xmllint validator, please check that it is installed correctly:\n" +
					"(On a Debian system, you need to have the package libxml2-utils installed)."+ ioe );
			return;
		}

		OutputStream outStream = new BufferedOutputStream( process.getOutputStream() );

		outStream.write( response.getText().getBytes() );
		outStream.close();

		InputStream errorInputStream = new BufferedInputStream( process.getErrorStream() );
		byte[] buffer = new byte[ 1024 ];
		int read = errorInputStream.read( buffer );
		if ( read > 0 )
		{
			String str = new String( buffer, 0, read );
			System.out.println( "Validation result:" );
			System.out.println( str );
			errorInputStream.close();
		}

		process.waitFor();
		String failMsg = "Validation failed, please look at the above message\n" +
			"(On a Debian system, be sure to have package w3c-dtd-xhtml installed).";
		assertEquals( failMsg, 0, process.exitValue() );
	}

	/**
	 * Returns the URL to access CartoWeb using a project
	 * 
	 * @param projectId
	 * @return
	 */
	private String getProjectUrl( String projectId )
	{

		return URL + "?project=" + projectId;
	}

	private void assertContainsMainmap( WebResponse response, boolean checkHtml ) throws Exception
	{

		HTMLElement mainmap = response.getElementWithID( "mapImageDiv" );
		saveAs( response, "last_checked_page.html" );
		assertNotNull( "No mainmap image on cartoclient page", mainmap );
		if (checkHtml)
			checkXHTMLValidity( response );
	}

	private void assertContainsMainmap( WebResponse response ) throws Exception
	{
		assertContainsMainmap( response, true );
	}
	
	private void testProject( WebConversation conversation, String projectId ) throws Exception
	{

		assertContainsMainmap( conversation.getCurrentPage() );
		WebResponse response = conversation.getCurrentPage();

		// click on the center of the keymap
		SubmitButton keymapButton = response.getFormWithName( "carto_form" ).getSubmitButton( "keymap" );
		keymapButton.click( 50, 50 );
		assertContainsMainmap( conversation.getCurrentPage() );
	}

	public void testDefaultProject() throws Exception
	{
		String projectId = "default";
		WebConversation conversation = new WebConversation();

		WebRequest request = new GetMethodWebRequest( getProjectUrl( projectId ) );
		WebResponse response = conversation.getResponse( request );

		saveAs( response, "first_display.html" );
		assertContainsMainmap( response );

		WebForm cartoForm = response.getFormWithName( "carto_form" );
		SubmitButton dummyButton = cartoForm.getSubmitButtons()[ 0 ];
		WebRequest webRequ = cartoForm.newUnvalidatedRequest( dummyButton );

		webRequ.setParameter( "tool", "query" );
		webRequ.setParameter( "selection_type", "rectangle" );
		webRequ.setParameter( "selection_coords", "204,109;204,109" );

		response = conversation.getResponse( webRequ );
		saveAs( response, "query_result.html" );
		// FIXME: Iso8859 / utf problems: no check for now ..
		assertContainsMainmap( response, false );

		testProject( conversation, projectId );
	}

	public void testTestprojectProject() throws Exception
	{
		String projectId = "testproject";
		WebConversation conversation = new WebConversation();
		WebRequest request = new GetMethodWebRequest( getProjectUrl( projectId ) );
		WebResponse response = conversation.getResponse( request );
		testProject( conversation, projectId );
		response = conversation.getResponse( getProjectUrl( projectId ) );
		assertTrue( "Testproject title does not contain \"test_project\"", response.getElementWithID( "banner" ).getText()
				.indexOf( "testproject" ) != -1 );

	}

	// FIXME: enable this once the demo project is xhtml valid
	public void OFF_testDemoProject() throws Exception
	{
		String projectId = "demo";
		WebConversation conversation = new WebConversation();
		WebRequest request = new GetMethodWebRequest( getProjectUrl( projectId ) );
		WebResponse response = conversation.getResponse( request );
		testProject( conversation, projectId );
	}
}
